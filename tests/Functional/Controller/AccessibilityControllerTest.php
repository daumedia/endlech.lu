<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;

/**
 * Feature 02 – Barrierefreiheitserklärung und Meldeformular.
 *
 * Prüft den Kern: die Seite ist öffentlich (AK-59), die Meldung geht per Mail
 * raus ohne gespeichert zu werden (AK-50/56), die E-Mail ist freiwillig (AK-49),
 * eine leere Beschreibung wird abgewiesen, und der Honeypot schluckt Bots (AK-53).
 */
final class AccessibilityControllerTest extends AbstractWebTestCase
{
    public function testStatementIsPubliclyAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/accessibility');

        // AK-59: ohne Anmeldung erreichbar.
        self::assertResponseIsSuccessful();
    }

    public function testSkipLinkIsPresent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/accessibility');

        // AK-01: Sprunglink zum Hauptinhalt vorhanden, Sprungziel existiert.
        self::assertSame('#hauptinhalt', $crawler->filter('body a')->first()->attr('href'));
        self::assertGreaterThan(0, $crawler->filter('main#hauptinhalt')->count());
    }

    public function testValidReportSendsMailAndConfirms(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/accessibility');

        $form = $this->formWithField($crawler, 'accessibility_report[description]', [
            'accessibility_report[description]' => 'Der Fokus ist auf der Startseite nicht sichtbar.',
            'accessibility_report[email]' => 'melder@example.com',
        ]);
        $client->submit($form);

        // AK-51: Erfolg (No-JS-Redirect auf die Seite).
        self::assertResponseRedirects();
        // AK-56: genau eine Mail an die Kontaktadresse.
        self::assertEmailCount(1);
    }

    public function testReportWithoutEmailIsAccepted(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/accessibility');

        $form = $this->formWithField($crawler, 'accessibility_report[description]', [
            'accessibility_report[description]' => 'Die Schrift ist zu klein.',
        ]);
        $client->submit($form);

        // AK-49: ohne E-Mail-Adresse geht die Meldung durch.
        self::assertResponseRedirects();
        self::assertEmailCount(1);
    }

    public function testEmptyDescriptionIsRejected(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/accessibility');

        $form = $this->formWithField($crawler, 'accessibility_report[description]', [
            'accessibility_report[description]' => '',
        ]);
        $client->submit($form);

        // Pflichtfeld: ungültiger Submit liefert 422, keine Mail.
        self::assertResponseStatusCodeSame(422);
        self::assertEmailCount(0);
    }

    public function testHoneypotIsSilentlyAccepted(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/accessibility');

        $form = $this->formWithField($crawler, 'accessibility_report[description]', [
            'accessibility_report[description]' => 'Legitim aussehende Meldung.',
            'accessibility_report[website]' => 'http://spam.example',
        ]);
        $client->submit($form);

        // AK-53: Bot bekommt dieselbe Erfolgsantwort, aber es wird nichts versendet.
        self::assertResponseRedirects();
        self::assertEmailCount(0);
    }
}
