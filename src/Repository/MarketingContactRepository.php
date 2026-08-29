<?php

namespace App\Repository;

use App\Entity\MarketingContact;
use App\Enum\MarketingSyncState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketingContact>
 */
class MarketingContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketingContact::class);
    }

    /**
     * Die Adresse ist der fachliche Schlüssel – eine Adresse, ein Kontakt.
     *
     * Kleingeschrieben nachgeschlagen, weil sie auch so gespeichert wird:
     * Sonst entstünden aus „Max@…" und „max@…" zwei Zeilen und der Empfänger
     * bekäme jede Kampagne doppelt (EC-01).
     */
    public function findOneByEmail(string $email): ?MarketingContact
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    /**
     * Die eine Abfrage des Sync-Laufs: was ist offen, ältestes zuerst.
     *
     * Deckt sich mit dem Index `(sync_state, updated_at)`. Zeilen, deren
     * Versuchszähler erschöpft ist, bleiben draußen – sie stehen sichtbar in
     * der Verwaltung und warten darauf, von Hand erneut angestoßen zu werden.
     * Ohne diesen Rückzug kostete jede dauerhaft scheiternde Zeile bei jedem
     * Lauf erneut einen Aufruf samt Wartezeit.
     *
     * ⚠ Gesperrte Zeilen bleiben draußen (AK-12): Wer sich abgemeldet hat, wird
     * nicht erneut eingetragen. Maßgeblich ist der jüngere Zeitpunkt – liegt
     * die Einwilligung nach dem Widerruf, ist die Sperre überholt und die
     * Adresse wieder frei (AK-45).
     *
     * ⚠ **Ein Löschauftrag ist von der Sperre ausgenommen.** Er muss gerade
     * dann durchkommen, wenn die Adresse gesperrt ist – sonst bliebe der
     * Kontakt bei Brevo stehen, obwohl lokal alles gelöscht ist.
     *
     * @return MarketingContact[]
     */
    public function findOpenForSync(int $limit): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.syncState IN (:states)')
            ->andWhere('m.attempts < :maxAttempts')
            ->andWhere('m.syncState = :removal OR m.revokedAt IS NULL OR m.consentAt > m.revokedAt')
            ->setParameter('states', [
                MarketingSyncState::PENDING,
                MarketingSyncState::REMOVAL_PENDING,
                // ⚠ BF-86: `FAILED` gehört hierher. Vorher fragte die Abfrage nur
                // die beiden erstgenannten Zustände ab – eine einmal
                // fehlgeschlagene Übertragung wurde damit **nie wieder**
                // aufgegriffen, entgegen AK-19. Ein einzelner 429 von Brevo
                // (bei Lastspitzen der Normalfall) fror den Kontakt dauerhaft
                // ein, ohne dass ein Alarm entstand.
                //
                // Den Rückzug besorgt `attempts < :maxAttempts` weiter unten –
                // darüber und nicht über den Zustand bleibt eine dauerhaft
                // scheiternde Zeile am Ende liegen.
                MarketingSyncState::FAILED,
            ])
            ->setParameter('removal', MarketingSyncState::REMOVAL_PENDING)
            ->setParameter('maxAttempts', MarketingContact::MAX_ATTEMPTS)
            ->orderBy('m.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Zählung je Zustand – Grundlage der Kopfzeile in der Verwaltung.
     *
     * @return array<string, int> Zustandswert => Anzahl
     */
    public function countBySyncState(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.syncState AS state, COUNT(m.id) AS total')
            ->groupBy('m.syncState')
            ->getQuery()
            ->getResult();

        $counts = [];

        foreach ($rows as $row) {
            $state = $row['state'];
            $counts[$state instanceof MarketingSyncState ? $state->value : (string) $state] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Wie viele Adressen tragen eine gültige Werbe-Einwilligung?
     *
     * Gegenstück zur Kontaktzahl in Brevo (AK-27). Gesperrte Zeilen zählen
     * nicht mit – sie stehen nur noch da, damit der nächste Lauf die Adresse
     * nicht erneut einträgt.
     */
    public function countConsented(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.revokedAt IS NULL OR m.consentAt > m.revokedAt')
            ->andWhere('m.syncState != :removal')
            ->setParameter('removal', MarketingSyncState::REMOVAL_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Zeilen zu einer Menge von Adressen – für die Wartelisten-Verwaltung,
     * die pro Seite bis zu einigen Dutzend Zeilen anzeigt.
     *
     * Eine einzelne Abfrage statt einer je Zeile: Die Liste lädt sonst bei
     * 50 Einträgen 50 Mal nach.
     *
     * @param string[] $emails
     *
     * @return array<string, MarketingContact> kleingeschriebene Adresse => Zeile
     */
    public function findIndexedByEmails(array $emails): array
    {
        $normalised = array_values(array_unique(array_filter(
            array_map(static fn (string $email): string => mb_strtolower(trim($email)), $emails),
            static fn (string $email): bool => '' !== $email,
        )));

        if ([] === $normalised) {
            return [];
        }

        $contacts = $this->createQueryBuilder('m')
            ->where('m.email IN (:emails)')
            ->setParameter('emails', $normalised)
            ->getQuery()
            ->getResult();

        $indexed = [];

        foreach ($contacts as $contact) {
            $indexed[$contact->getEmail()] = $contact;
        }

        return $indexed;
    }
}
