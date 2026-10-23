<?php

namespace App\Marketing;

use Doctrine\DBAL\ArrayParameterType;
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
        /** @var list<int> $angelegt Zeilen, die dieser Lauf bei Brevo angelegt oder fortgeschrieben hat */
        $angelegt = [];
        /** @var list<int> $gesperrt Zeilen, deren Sperre dieser Lauf übertragen hat */
        $gesperrt = [];

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

            // Feature 12: Abbestellt über Endlech.lu — die Zeile ist gesperrt, der Kontakt
            // muss es bei Brevo auch werden. Steht vor der Sperrprüfung darunter, die sonst
            // genau diesen Auftrag überspränge.
            if (MarketingSyncState::BLACKLIST_PENDING === $contact->getSyncState()) {
                if ($this->blacklistOne($contact)) {
                    ++$synced;
                    $gesperrt[] = (int) $contact->getId();
                } else {
                    ++$failed;
                }

                continue;
            }

            // Gürtel und Hosenträger: Die Abfrage filtert Gesperrte bereits
            // heraus (AK-12). Sollte sich das je ändern, geht hier trotzdem
            // keine Werbung an jemanden, der widersprochen hat.
            if ($contact->isBlocked()) {
                ++$skipped;

                continue;
            }

            if ($this->upsertOne($contact)) {
                ++$synced;
                $angelegt[] = (int) $contact->getId();
            } else {
                ++$failed;
            }
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

        $this->abmeldungenWaehrendDesLaufsNachtragen($angelegt);
        $this->einwilligungenWaehrendDesLaufsNachtragen($gesperrt);

        return new MarketingSyncResult(
            synced: $synced,
            removed: $removed,
            failed: $failed,
            skipped: $skipped,
        );
    }

    /**
     * ⚠ BF-178: das Spiegelbild von BF-166. Abonniert jemand erneut, WÄHREND dieser Lauf die Sperre
     * überträgt, schrieb der Lauf danach `synced` über das `pending` der neuen Einwilligung — Brevo blieb
     * gesperrt, das Profil sagte „abonniert". Deshalb: Ist eine eben gesperrte Zeile inzwischen wieder
     * eingewilligt, geht sie zurück auf `pending`, und `synced_at` wird geleert, damit der Mapper die
     * Aufhebung mitschickt (seine Bedingung vergleicht mit `synced_at`).
     *
     * @param list<int> $ids
     */
    private function einwilligungenWaehrendDesLaufsNachtragen(array $ids): void
    {
        if ([] === $ids) {
            return;
        }

        try {
            $this->entityManager->getConnection()->executeStatement(
                'UPDATE marketing_contact SET sync_state = :offen, synced_at = NULL, attempts = 0, last_error = NULL'
                .' WHERE id IN (:ids) AND sync_state = :uebertragen AND revoked_at IS NOT NULL AND consent_at > revoked_at',
                [
                    'offen' => MarketingSyncState::PENDING->value,
                    'uebertragen' => MarketingSyncState::SYNCED->value,
                    'ids' => $ids,
                ],
                ['ids' => ArrayParameterType::INTEGER],
            );
        } catch (\Throwable $e) {
            $this->logger->error('Brevo-Kontaktabgleich: Nachtrag von Wieder-Einwilligungen gescheitert', [
                'reason' => $e::class,
                'betroffen' => \count($ids),
            ]);
        }
    }

    /**
     * ⚠ BF-166: Eine Abmeldung, die eintrifft, WÄHREND dieser Lauf den Kontakt bei Brevo anlegt,
     * erreichte Brevo nie. Der Lauf hatte die Zeile am Anfang geladen und schrieb am Ende `synced`;
     * die zweite Anfrage sah noch keine Übertragung und setzte nur `revoked_at` — danach war die Zeile
     * gesperrt, bei Brevo aber offen, und kein Auftrag stand mehr aus.
     *
     * Deshalb nach dem Schreiben frisch nachsehen: Ist eine eben übertragene Zeile inzwischen gesperrt,
     * bekommt sie den Sperrauftrag, den die zweite Anfrage nicht stellen konnte. Bedingt geschrieben
     * (`sync_state = 'synced'`), damit ein zwischenzeitlich gestellter Lösch- oder Sperrauftrag bleibt.
     * Eine Abmeldung, die erst NACH diesem Schreiben kommt, sieht `synced_at` und stellt den Auftrag
     * selbst.
     *
     * @param list<int> $ids
     */
    private function abmeldungenWaehrendDesLaufsNachtragen(array $ids): void
    {
        if ([] === $ids) {
            return;
        }

        try {
            $this->entityManager->getConnection()->executeStatement(
                'UPDATE marketing_contact SET sync_state = :sperre, attempts = 0, last_error = NULL'
                .' WHERE id IN (:ids) AND sync_state = :uebertragen AND revoked_at IS NOT NULL AND consent_at <= revoked_at',
                [
                    'sperre' => MarketingSyncState::BLACKLIST_PENDING->value,
                    'uebertragen' => MarketingSyncState::SYNCED->value,
                    'ids' => $ids,
                ],
                ['ids' => ArrayParameterType::INTEGER],
            );
        } catch (\Throwable $e) {
            // Wie der `flush()` darüber: protokollieren, nicht abbrechen. Der nächste Abbestell-Vorgang
            // oder ein erneuter Lauf über dieselbe Zeile holt es nicht von selbst nach — deshalb laut.
            $this->logger->error('Brevo-Kontaktabgleich: Nachtrag von Abmeldungen gescheitert', [
                'reason' => $e::class,
                'betroffen' => \count($ids),
            ]);
        }
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
     * Sperrauftrag ausführen (Feature 12, AK-36).
     *
     * Danach steht die Zeile auf `synced` und bleibt gesperrt — wie nach einer Abmeldung über
     * den Link einer Brevo-Mail. Sie wird nicht gelöscht: Die Sperre ist auch hier die
     * Erinnerung daran, dass dieser Mensch nichts mehr bekommen will (04/AK-12).
     */
    private function blacklistOne(MarketingContact $contact): bool
    {
        $id = $contact->getId();

        if (null === $id) {
            return false;
        }

        try {
            $this->client->blacklist($id);
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
