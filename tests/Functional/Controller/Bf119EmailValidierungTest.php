<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Repository\OrganisationWaitlistEntryRepository;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * BF-119 — eine Adresse, die der Mailversand nicht annimmt, muss am Formular
 * scheitern: 422, kein 500, und **keine** Zeile.
 *
 * Gefunden am 2026-09-05 bei der QA von Feature `08`, dort behoben, für B01,
 * B14 und B15 offen geblieben. `new Email(...)` lief an diesen drei im
 * HTML5-Default und ließ Adressen durch, die `Mime\Address` nach RFC 2822
 * ablehnt; der Versand warf dann eine `RfcComplianceException` — und weil vor
 * dem Versand gespeichert wird, blieb die Zeile stehen. Gemessen: 0 → 1.
 *
 * ⚠ Geprüft werden **beide Hälften**: der Statuscode *und* der Bestand. Ein
 * Test, der nur auf 422 sieht, bliebe grün, wenn die Prüfung zwar greift, die
 * Zeile aber trotzdem entsteht — genau die Kombination war der Befund.
 */
final class Bf119EmailValidierungTest extends AbstractWebTestCase
{
    /**
     * Adressen, die der HTML5-Default durchlässt und `Mime\Address` ablehnt.
     *
     * ⚠ **Nur solche Adressen sichern die Reparatur ab (BF-127).** Die erste
     * Fassung führte auch `a"b(c)d@example.lu` und `jemand@@example.lu` — beide
     * weist schon der **Default** ab, sie lösten den Befund also nie aus und
     * blieben auch bei entferntem STRICT grün. Belegt von zwei Seiten: gegen den
     * Validator im HTML5-Modus gemessen, und in der Rückbau-Gegenprobe wurde
     * **1 von 3** Datensätzen rot. Ersetzt wurden sie durch drei Formen, die der
     * Default durchlässt (doppelter, führender und abschliessender Punkt im
     * Local-Part).
     *
     * ⚠ **`Bf127AngriffsadressenTest` prüft diese Liste selbst** — jeder Eintrag
     * muss vom Default angenommen und von `Mime\Address` abgelehnt werden. Ohne
     * diesen Lauf kann der nächste gut gemeinte Datensatz wieder wirkungslos
     * einschlafen, ohne dass es jemandem auffällt.
     *
     * @return iterable<string, array{string}>
     */
    public static function rfcWidrigeAdressen(): iterable
    {
        yield 'Pfad im Local-Part' => ['../../etc/passwd@example.lu'];
        yield 'doppelter Punkt im Local-Part' => ['anna..muster@example.lu'];
        yield 'führender Punkt im Local-Part' => ['.anna@example.lu'];
        yield 'abschliessender Punkt im Local-Part' => ['anna.@example.lu'];
    }

    /**
     * Adressen, die weiterhin durchgehen müssen.
     *
     * ⚠ Der eigentliche Prüfpunkt dieser Reparatur. STRICT ist schärfer als der
     * Default — wäre es zu scharf, verlöre die Plattform Anmeldungen, und das
     * fiele niemandem auf, weil ein abgewiesener Interessent sich nicht
     * beschwert. `jean-luc@télécom.lu` steht bewusst dabei: Der **Default**
     * wies es ab, STRICT nimmt es an.
     *
     * @return iterable<string, array{string}>
     */
    public static function gueltigeAdressen(): iterable
    {
        yield 'schlicht' => ['anna@example.lu'];
        yield 'Plus-Adressierung' => ['anna+partner@example.lu'];
        yield 'Punkt im Local-Part' => ['anna.muster@example.lu'];
        yield 'Bindestrich-Domain' => ['anna@brasserie-test.lu'];
        yield 'Subdomain' => ['anna@mail.example.lu'];
        yield 'Umlaut-Domain' => ['jean-luc@télécom.lu'];
    }

    // ─── B01 · Registrierung ────────────────────────────────────────────────

    #[DataProvider('rfcWidrigeAdressen')]
    public function testRegistrierungWeistRfcWidrigeAdresseAb(string $adresse): void
    {
        $client = static::createClient();
        $this->registriere($client, $adresse);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNull(
            $client->getContainer()->get(UserRepository::class)->findOneBy(['email' => $adresse]),
            'BF-119: Eine abgewiesene Adresse darf kein Konto hinterlassen.',
        );
        self::assertEmailCount(0);
    }

    #[DataProvider('gueltigeAdressen')]
    public function testRegistrierungNimmtGueltigeAdresseAn(string $adresse): void
    {
        $client = static::createClient();
        $this->registriere($client, $adresse);

        self::assertResponseRedirects();
        self::assertNotNull(
            $client->getContainer()->get(UserRepository::class)->findOneBy(['email' => $adresse]),
            "STRICT darf {$adresse} nicht abweisen.",
        );
    }

    // ─── B14 · Partner-Warteliste ───────────────────────────────────────────

    #[DataProvider('rfcWidrigeAdressen')]
    public function testPartnerWeistRfcWidrigeAdresseAb(string $adresse): void
    {
        $client = static::createClient();
        $vorher = $client->getContainer()->get(PartnerWaitlistEntryRepository::class)->count([]);

        $this->sendePartner($client, $adresse);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame(
            $vorher,
            $client->getContainer()->get(PartnerWaitlistEntryRepository::class)->count([]),
            'BF-119: Eine abgewiesene Adresse darf keinen Eintrag hinterlassen.',
        );
        self::assertEmailCount(0);
    }

    #[DataProvider('gueltigeAdressen')]
    public function testPartnerNimmtGueltigeAdresseAn(string $adresse): void
    {
        $client = static::createClient();
        $this->sendePartner($client, $adresse);

        self::assertResponseRedirects(self::LOCALE.'/partner');
    }

    // ─── B15 · Organisations-Warteliste ─────────────────────────────────────

    #[DataProvider('rfcWidrigeAdressen')]
    public function testOrganisationWeistRfcWidrigeAdresseAb(string $adresse): void
    {
        $client = static::createClient();
        $vorher = $client->getContainer()->get(OrganisationWaitlistEntryRepository::class)->count([]);

        $this->sendeOrganisation($client, $adresse);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame(
            $vorher,
            $client->getContainer()->get(OrganisationWaitlistEntryRepository::class)->count([]),
            'BF-119: Eine abgewiesene Adresse darf keinen Eintrag hinterlassen.',
        );
        self::assertEmailCount(0);
    }

    #[DataProvider('gueltigeAdressen')]
    public function testOrganisationNimmtGueltigeAdresseAn(string $adresse): void
    {
        $client = static::createClient();
        $this->sendeOrganisation($client, $adresse);

        self::assertResponseRedirects();
    }

    // ─── Hilfen ─────────────────────────────────────────────────────────────

    private function registriere(KernelBrowser $client, string $adresse): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/register');

        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'BF119 Test',
            'registration[email]' => $adresse,
            'registration[plainPassword][first]' => 'supersecret',
            'registration[plainPassword][second]' => 'supersecret',
        ]));
    }

    private function sendePartner(KernelBrowser $client, string $adresse): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/partner');

        $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', [
            'partner_waitlist[restaurantName]' => 'Brasserie BF119',
            'partner_waitlist[contactName]' => 'Anna Muster',
            'partner_waitlist[email]' => $adresse,
            'partner_waitlist[locality]' => 'Strassen',
            'partner_waitlist[consent]' => true,
        ]));
    }

    private function sendeOrganisation(KernelBrowser $client, string $adresse): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/organisationen');

        $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', [
            'organisation_waitlist[type]' => 'company',
            'organisation_waitlist[organisationName]' => 'Testorganisation BF119',
            'organisation_waitlist[contactName]' => 'Alex Muster',
            'organisation_waitlist[email]' => $adresse,
            'organisation_waitlist[consent]' => true,
        ]));
    }
}
