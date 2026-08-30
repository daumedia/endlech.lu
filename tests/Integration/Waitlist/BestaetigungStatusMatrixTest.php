<?php

namespace App\Tests\Integration\Waitlist;

use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Marketing\MarketingPayloadMapper;
use App\Repository\MarketingContactRepository;
use App\Waitlist\WaitlistConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Was `confirm()` je nach Ausgangsstatus tut — die vollständige Matrix.
 *
 * ⚠ **An dieser Methode ist Feature 04 dreimal hintereinander gescheitert**
 * (BF-83 → BF-89 → BF-91). Jedes Mal war die Änderung für sich richtig und
 * traf einen Fall, an den niemand gedacht hatte. Dieser Test hält deshalb
 * **alle sechs Ausgangszustände** fest, nicht nur den, um den es gerade ging —
 * samt ihrer Wirkung bis in das Brevo-Attribut.
 *
 * Wer `confirm()` das nächste Mal anfasst, sieht hier sofort, was er umwirft.
 */
final class BestaetigungStatusMatrixTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private WaitlistConfirmationService $service;
    private MarketingContactRepository $contacts;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(WaitlistConfirmationService::class);
        $this->contacts = static::getContainer()->get(MarketingContactRepository::class);
    }

    private function eintrag(WaitlistStatus $status, string $email): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Matrix')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->generateConfirmationToken();
        $entry->setStatus($status);

        // Das tut `applyStatus()` bei jedem Wechsel weg von PENDING.
        if (WaitlistStatus::PENDING !== $status) {
            $entry->setConfirmedAt(new \DateTimeImmutable());
        }

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    /**
     * ⚠ Nur aus `PENDING` heraus wechselt der Status. Jeder fortgeschrittene
     * Vertriebsstand ist die jüngere Information und bleibt stehen (BF-91).
     */
    public function testNurAusPendingWechseltDerStatus(): void
    {
        foreach (WaitlistStatus::cases() as $i => $ausgang) {
            $entry = $this->eintrag($ausgang, "matrix-status-{$i}@qa.lu");

            self::assertSame(
                WaitlistConfirmationService::RESULT_CONFIRMED,
                $this->service->confirm($entry),
                "Aus {$ausgang->value} heraus wurde die Bestätigung nicht angenommen",
            );

            $erwartet = WaitlistStatus::PENDING === $ausgang ? WaitlistStatus::CONFIRMED : $ausgang;

            self::assertSame(
                $erwartet,
                $entry->getStatus(),
                "Aus {$ausgang->value} heraus ist der Status auf {$entry->getStatus()->value} gesprungen",
            );
        }
    }

    /**
     * Die Selbstbestätigung wird in **jedem** Fall festgehalten — sie ist
     * eingetreten, unabhängig vom Vertriebsstand.
     */
    public function testDieSelbstbestaetigungWirdImmerFestgehalten(): void
    {
        foreach (WaitlistStatus::cases() as $i => $ausgang) {
            $entry = $this->eintrag($ausgang, "matrix-self-{$i}@qa.lu");
            $this->service->confirm($entry);

            self::assertTrue(
                $entry->hasSelfConfirmed(),
                "Aus {$ausgang->value} heraus fehlt die Selbstbestätigung",
            );
        }
    }

    /**
     * ⚠ Dieselbe Zusicherung für den **Organisations**-Weg.
     *
     * `confirm()` steht in beiden Entities getrennt — eine Reparatur, die nur
     * eine von beiden trifft, fällt ohne diesen Test nicht auf. Genau das war
     * die Lücke in der Prüfung des vierten Durchlaufs.
     */
    public function testDieselbeRegelGiltFuerOrganisationen(): void
    {
        $entry = new OrganisationWaitlistEntry();
        $entry->setType(OrganisationType::COMMUNE)
            ->setOrganisationName('Gemeng Gewonnen')
            ->setContactName('Kontakt')
            ->setEmail('matrix-org@qa.lu')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->generateConfirmationToken();
        $entry->setConfirmedAt(new \DateTimeImmutable());
        $entry->setStatus(WaitlistStatus::CONVERTED);

        $this->em->persist($entry);
        $this->em->flush();

        $this->service->confirm($entry);
        $this->em->flush();

        self::assertSame(WaitlistStatus::CONVERTED, $entry->getStatus(), 'Der Vertriebsstand ist zurückgefallen');
        self::assertTrue($entry->hasSelfConfirmed());

        $kontakt = $this->contacts->findOneByEmail('matrix-org@qa.lu');
        $payload = (new MarketingPayloadMapper('7'))->toBrevoPayload($kontakt);

        self::assertSame('converted', $payload['attributes']['FUNNEL_STATUS']);
        self::assertSame('COMMUNE', $payload['attributes']['ORIGIN'], 'Die Herkunft wurde falsch abgeleitet');
    }

    /**
     * ⚠ AK-08: Der Vertriebsstand, den Brevo bekommt, ist der **tatsächliche**.
     *
     * Über dieses Attribut schließt eine Kampagne fürs Partnerprogramm die
     * bereits gewonnenen Häuser aus. Ein falscher Wert hier erreicht Menschen,
     * mit denen der Vorgang abgeschlossen ist.
     */
    public function testAk08DerVertriebsstandErreichtBrevoUnverfaelscht(): void
    {
        $mapper = new MarketingPayloadMapper('7');

        foreach (WaitlistStatus::cases() as $i => $ausgang) {
            $email = "matrix-brevo-{$i}@qa.lu";
            $entry = $this->eintrag($ausgang, $email);

            $this->service->confirm($entry);
            $this->em->flush();

            $kontakt = $this->contacts->findOneByEmail($email);

            self::assertNotNull($kontakt, "Aus {$ausgang->value} heraus entstand kein Kontakt");

            $erwartet = WaitlistStatus::PENDING === $ausgang ? 'confirmed' : $ausgang->value;

            self::assertSame(
                $erwartet,
                $mapper->toBrevoPayload($kontakt)['attributes']['FUNNEL_STATUS'],
                "Aus {$ausgang->value} heraus ging der falsche Vertriebsstand nach Brevo",
            );
        }
    }
}
