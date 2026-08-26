<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;

/**
 * Feature 02 – eingeloggte Barrierefreiheits-Aspekte, die einen Login voraussetzen.
 * Ergänzt den axe-Lauf und die Browser-Interaktionstests (public) um die
 * geschützten Wege. loginUser() umgeht Login-Formular/CSRF (Projekt-Standard).
 */
final class AccessibilityInteractionTest extends AbstractWebTestCase
{
    public function testWizardHasAriaLiveAnnouncer(): void // AK-24
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $crawler = $client->request('GET', self::LOCALE.'/community/suggest');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(
            0,
            $crawler->filter('[data-suggestion-wizard-target="announcer"][aria-live]')->count(),
            'Der Wizard braucht eine aria-live-Region für die Schrittansage (AK-24).',
        );
    }

    public function testProfileHasKeyboardReachableLogout(): void // AK-34
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $crawler = $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(
            0,
            $crawler->filter('form[action$="/logout"] button[type="submit"]')->count(),
            'Die Profilseite braucht einen Abmelden-Knopf (AK-34, mobiler Weg über die Bottom-Nav).',
        );
    }

    public function testAdminEditFormHasNoOutlineNone(): void // AK-40
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $crawler = $client->request('GET', self::LOCALE.'/admin/restaurants');
        $editLinks = $crawler->filter('a[href*="/bearbeiten"]');
        if ($editLinks->count() === 0) {
            self::markTestSkipped('Kein Restaurant zum Bearbeiten in der Test-DB.');
        }
        $crawler = $client->click($editLinks->first()->link());

        self::assertResponseIsSuccessful();
        // Nur der <main>-Inhalt: die dev/test-Debug-Toolbar am Body-Ende trägt eigenes
        // outline-none und ist in prod nicht vorhanden. T15 hat im Admin jede
        // `focus:ring … outline-none`-Fundstelle auf echte `outline` umgestellt (AK-40).
        self::assertStringNotContainsString(
            'outline-none',
            $crawler->filter('main')->html(),
            'Das Admin-Bearbeiten-Formular darf kein focus:outline-none tragen (AK-40).',
        );
    }

    public function testProfileMainHasNoOutlineNone(): void // AK-04 / AK-40 im Profil — belegt BF-76
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $crawler = $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(
            'outline-none',
            $crawler->filter('main')->html(),
            'Die Passkey-Verwaltung (_passkey_manage.html.twig) trägt noch focus:outline-none — '
            .'im Windows-Kontrastmodus verschwindet der Fokus (BF-76, AK-04/AK-40).',
        );
    }
}
