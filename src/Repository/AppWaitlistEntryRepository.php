<?php

namespace App\Repository;

use App\Entity\AppWaitlistEntry;
use App\Enum\AppPlatform;
use App\Enum\WaitlistStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppWaitlistEntry>
 */
class AppWaitlistEntryRepository extends ServiceEntityRepository
{
    /** Nie selbst bestätigte Vormerkungen werden nach dieser Zeit gelöscht (AK-47). */
    public const string STALE_AFTER = '-30 days';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppWaitlistEntry::class);
    }

    public function findOneByConfirmationToken(string $token): ?AppWaitlistEntry
    {
        return $this->findOneBy(['confirmationToken' => $token]);
    }

    /**
     * ⚠ Sucht mit derselben Normalisierung, mit der `setEmail()` schreibt.
     * Ohne sie fände eine Anfrage mit `Max@Example.LU` den Eintrag nicht — und
     * der Dublettenzweig (AK-15) legte eine zweite Zeile an, an der dann der
     * Unique-Index scheiterte.
     */
    public function findOneByEmail(string $email): ?AppWaitlistEntry
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    /**
     * Admin-Liste: optional nach Status gefiltert, nach Eingangsdatum sortiert.
     *
     * @return AppWaitlistEntry[]
     */
    public function findFiltered(?WaitlistStatus $status = null, string $direction = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('a');

        if (null !== $status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        $qb->orderBy('a.createdAt', 'ASC' === strtoupper($direction) ? 'ASC' : 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Zahl der **selbst bestätigten** Vormerkungen je Plattform (AK-38).
     *
     * ⚠ Gezählt wird an `selfConfirmedAt`, nicht am Status. Eine Zahl, die
     * unbestätigte Vormerkungen mitnimmt, wäre über das Formular beliebig
     * aufblasbar — und sie steht auf einer Transparenzseite.
     *
     * @return array<string, int> Schlüssel sind die Werte von AppPlatform;
     *                            jede Plattform kommt vor, auch mit 0
     */
    public function countConfirmedByPlatform(): array
    {
        $counts = array_fill_keys(AppPlatform::values(), 0);

        $rows = $this->createQueryBuilder('a')
            ->select('a.platform AS platform, COUNT(a.id) AS total')
            ->where('a.selfConfirmedAt IS NOT NULL')
            ->groupBy('a.platform')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $platform = $row['platform'];
            $key = $platform instanceof AppPlatform ? $platform->value : (string) $platform;

            if (\array_key_exists($key, $counts)) {
                $counts[$key] = (int) $row['total'];
            }
        }

        return $counts;
    }

    /**
     * Anzahl je Status, für die Filter-Pills im Admin.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.status AS status, COUNT(a.id) AS total')
            ->groupBy('a.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];

        foreach ($rows as $row) {
            $status = $row['status'];
            $counts[$status instanceof WaitlistStatus ? $status->value : (string) $status] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Löscht nie selbst bestätigte Vormerkungen, deren **Erstkontakt** älter als
     * $date ist.
     *
     * ⚠ **Bedingung ist `selfConfirmedAt IS NULL`, nicht `status = pending`.**
     * Ein Eintrag, den ein Admin von Hand weitergesetzt hat, steht nicht mehr
     * auf `pending` und entginge dem Lauf — obwohl nie jemand bestätigt hat und
     * damit keine Einwilligung vorliegt (dieselbe Zweideutigkeit wie BF-89).
     *
     * ⚠ **Gemessen wird an `consentAt`, nicht an `createdAt` (BF-122).**
     * Beide Spalten trugen ursprünglich dieselbe Frist — bis
     * `renewConfirmationWindow()` dazukam, das `createdAt` zurücksetzt, damit
     * ein abgelaufener Bestätigungslink neu ausgestellt werden kann (BF-117).
     * Seither hätte ein Absendevorgang alle 7 bis 29 Tage die Aufbewahrung
     * unbegrenzt verlängert — nachgestellt: der Lauf an Tag 31 löschte **null**.
     * Und weil der Weg keine Eigentümerschaft prüft, ginge das mit **fremden**
     * Adressen.
     *
     * `consentAt` bleibt beim Erneuern unberührt und markiert den echten
     * Erstkontakt. Damit ist der Bestätigungslink weiterhin erneuerbar, die
     * Aufbewahrung aber gedeckelt — was AK-47 zusagt und datenschutzrechtlich
     * begründet ist: Ohne eingelöste Bestätigung liegt keine Einwilligung vor,
     * und daran ändert ein weiterer Absendevorgang nichts.
     *
     * ⚠ Es braucht **keine** zweite Bedingung neben `createdAt`: Da `consentAt`
     * nie später liegt als `createdAt`, ist es von beiden stets das schärfere
     * Kriterium. Ein `OR` daneben wäre wirkungslos und würde nur so aussehen,
     * als prüfte es etwas.
     *
     * Eine einzige DQL-Anweisung statt Laden-und-Löschen: Der Lauf soll auch
     * bei tausend Zeilen nicht den Speicher füllen.
     *
     * @return int Zahl der gelöschten Zeilen
     */
    public function deleteStaleUnconfirmed(\DateTimeInterface $date): int
    {
        return (int) $this->createQueryBuilder('a')
            ->delete()
            ->where('a.selfConfirmedAt IS NULL')
            ->andWhere('a.consentAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
