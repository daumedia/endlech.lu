<?php

namespace App\Marketing;

use App\Entity\MarketingContact;
use App\Enum\MarketingSyncState;
use App\Repository\MarketingContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Arbeitet das Auftragsbuch ab – der einzige Ort im Projekt, der Brevos
 * Kontaktverwaltung ruft (Feature 04).
 *
 * Läuft ausschließlich aus einem Konsolenbefehl heraus, nie in einer Anfrage.
 * Damit hängt keine Anmeldung an der Erreichbarkeit eines fremden Dienstes
 * (AK-17), und ein Ausfall verzögert die Übertragung, statt sie zu verlieren.
 */
class MarketingSyncService
{
    public function __construct(
        private readonly MarketingContactRepository $contacts,
        private readonly MarketingPayloadMapper $mapper,
        private readonly BrevoContactClient $client,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.brevo_sync_batch%')]
        private readonly int $batchSize = 200,
        #[Autowire('%app.brevo_sync_delay_ms%')]
        private readonly int $delayMs = 200,
    ) {
    }

    /**
     * Ein Durchlauf.
     *
     * ⚠ **Fehlt der Schlüssel, wird nichts angefasst** (AK-47). Die Aufträge
     * bleiben stehen und gehen beim ersten Lauf mit gesetztem Schlüssel hinaus
     * – die Einwilligung geht nicht verloren, sie wartet.
     *
     * @param int|null $limit überschreibt den Deckel dieses Laufs (für Tests
     *                        und für einen bewusst kleinen ersten Lauf)
     */
    public function run(?int $limit = null): MarketingSyncResult
    {
        if (!$this->client->isConfigured()) {
            return new MarketingSyncResult(configured: false);
        }

        // AK-39: Deckel je Lauf. Der Rest bleibt im Auftragsbuch und kommt
        // beim nächsten Durchgang – ein einzelner Lauf gefährdet damit weder
        // das Kontingent des Anbieters noch die Zustellrate.
        $open = $this->contacts->findOpenForSync($limit ?? $this->batchSize);

        $synced = 0;
        $removed = 0;
        $failed = 0;
        $skipped = 0;
        $first = true;

        foreach ($open as $contact) {
            // AK-39: Mindestabstand zwischen zwei Aufrufen. Vor dem ersten
            // nicht – dort gäbe es nichts, wovon Abstand zu halten wäre.
            if (!$first && $this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
            $first = false;

            if (MarketingSyncState::REMOVAL_PENDING === $contact->getSyncState()) {
                $this->removeOne($contact) ? ++$removed : ++$failed;

                continue;
            }

            // Gürtel und Hosenträger: Die Abfrage filtert Gesperrte bereits
            // heraus (AK-12). Sollte sich das je ändern, geht hier trotzdem
            // keine Werbung an jemanden, der widersprochen hat.
            if ($contact->isBlocked()) {
                ++$skipped;

                continue;
            }

            $this->upsertOne($contact) ? ++$synced : ++$failed;
        }

        // ⚠ BF-87: Ein einziger `flush()` für den ganzen Lauf – bis zu
        // `app.brevo_sync_batch` Zeilen in einer Transaktion. Scheitert er
        // (Lock-Timeout, Deadlock), gehen die Zustände aller bereits
        // übertragenen Zeilen verloren. Wegen der Idempotenz von `upsert`
        // und `delete` ist das kein Korrektheitsproblem – der nächste Lauf
        // holt sie nach –, aber es kostet unnötige Aufrufe bei Brevo und
        // verzögert, bis `last_error` in der Verwaltung sichtbar wird.
        //
        // Gefangen wird er trotzdem: Eine unbehandelte Ausnahme ließe den
        // Cron-Job scheitern und verschwiege, dass die Aufrufe bereits
        // stattgefunden haben.
        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Brevo-Kontaktabgleich: Zustände konnten nicht gespeichert werden', [
                'reason' => $e::class,
                'betroffen' => \count($open),
            ]);

            return new MarketingSyncResult(
                synced: 0,
                removed: 0,
                failed: $synced + $removed + $failed,
                skipped: $skipped,
            );
        }

        return new MarketingSyncResult(
            synced: $synced,
            removed: $removed,
            failed: $failed,
            skipped: $skipped,
        );
    }

    /**
     * Anlegen oder Fortschreiben – bei Brevo derselbe Aufruf.
     */
    private function upsertOne(MarketingContact $contact): bool
    {
        try {
            $this->client->upsert($this->mapper->toBrevoPayload($contact));
            $contact->markSynced();

            return true;
        } catch (\Throwable $e) {
            $this->recordFailure($contact, $e);

            return false;
        }
    }

    /**
     * Löschauftrag ausführen.
     *
     * Erst wenn Brevo die Löschung bestätigt hat, verschwindet die Zeile. Bis
     * dahin bleibt sie stehen – sonst wäre der Auftrag weg und der Kontakt
     * dort geblieben.
     */
    private function removeOne(MarketingContact $contact): bool
    {
        $id = $contact->getId();

        if (null === $id) {
            return false;
        }

        try {
            $this->client->delete($id);
            $this->entityManager->remove($contact);

            return true;
        } catch (\Throwable $e) {
            $this->recordFailure($contact, $e);

            return false;
        }
    }

    /**
     * ⚠ Protokolliert **Klasse und Meldung der eigenen Ausnahme**, nie die
     * Antwort des Dienstes im Wortlaut und nie eine vollständige Adresse
     * (AK-31). `BrevoRequestFailed` trägt bereits nur eine sichere Kurzform;
     * bei jeder anderen Ausnahme geht ausschließlich der Klassenname mit.
     */
    private function recordFailure(MarketingContact $contact, \Throwable $e): void
    {
        $reason = $e instanceof BrevoRequestFailed
            ? $e->getMessage()
            : $e::class;

        $contact->markFailed($reason);

        $this->logger->error('Brevo-Kontaktabgleich fehlgeschlagen', [
            'contact_id' => $contact->getId(),
            'state' => $contact->getSyncState()->value,
            'attempts' => $contact->getAttempts(),
            'reason' => $reason,
        ]);
    }
}
