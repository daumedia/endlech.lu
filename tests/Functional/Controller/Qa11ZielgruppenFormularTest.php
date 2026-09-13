<?php

namespace App\Tests\Functional\Controller;

use App\Entity\OrganisationWaitlistEntry;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * QA Feature 11 · Befund BF-151 (B15) — **Reproduktion, übersprungen bis zur Behebung.**
 *
 * Das Formular auf den Zielgruppenseiten `/{locale}/organisationen/{gemeinden|unternehmen|vereine}` trägt kein
 * `action`. Ein Browser schickt es deshalb an die Zielgruppenseite selbst — die kennt nur GET, die POST-Route
 * `app_organisations_submit` liegt unter `/{locale}/organisationen`. Gemessen im echten Browser
 * (`qa/11/wartelisten-browser.ausgabe.txt`): **405**, Symfonys Fehlerseite „Oops! An Error Occurred", keine Zeile
 * gespeichert. Auf der Produktion rendert dieselbe Seite dasselbe Formular ohne `action`.
 *
 * ⚠ Warum `OrganisationControllerTest` es nicht sah: Jeder Absende-Test holt das Formular von der Übersicht
 * (`/organisationen`), wo die aktuelle Adresse zufällig die POST-Route ist. Der Testclient schickt ein Formular
 * ohne `action` wie ein Browser an die Adresse, von der es kam — dieser Lauf holt es deshalb von der Zielgruppenseite.
 *
 * `sdd-build`: die Zeile mit `markTestSkipped` entfernen, sobald behoben.
 */
final class Qa11ZielgruppenFormularTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function zielgruppen(): iterable
    {
        yield 'Gemeinden' => ['gemeinden', 'commune'];
        yield 'Unternehmen' => ['unternehmen', 'company'];
        yield 'Vereine' => ['vereine', 'association'];
    }

    #[DataProvider('zielgruppen')]
    public function testBf151AbsendenVonDerZielgruppenseite(string $slug, string $typ): void
    {
        self::markTestSkipped('BF-151 offen — Reproduktion für sdd-build, siehe features/11-nutzungsmessung/qa-report.md');

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/organisationen/'.$slug);
        $adresse = 'qa11-'.$slug.'@example.lu';

        $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', [
            'organisation_waitlist[type]' => $typ,
            'organisation_waitlist[organisationName]' => 'QA11 '.$slug,
            'organisation_waitlist[contactName]' => 'Alex Muster',
            'organisation_waitlist[email]' => $adresse,
            'organisation_waitlist[consent]' => true,
        ]));

        self::assertNotSame(405, $client->getResponse()->getStatusCode(), 'Das Formular der Zielgruppenseite muss an die POST-Route gehen.');
        self::assertLessThan(400, $client->getResponse()->getStatusCode());
        $eintrag = static::getContainer()->get('doctrine')->getRepository(OrganisationWaitlistEntry::class)->findOneBy(['email' => $adresse]);
        self::assertNotNull($eintrag, 'Die Anmeldung muss gespeichert sein.');
    }
}
