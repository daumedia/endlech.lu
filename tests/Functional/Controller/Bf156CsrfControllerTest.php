<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;

/**
 * BF-156 (B02) — nach einem Formular mit Double-Submit scheiterte jede frisch geladene Anmeldung
 * („Ungültiges CSRF-Token.") und die Abmeldung lief lautlos ins Leere.
 *
 * ⚠ **Der Server verhält sich richtig und bleibt, wie er ist.** Symfony merkt sich in der Sitzung,
 * dass Double-Submit benutzt wurde, und verlangt es danach für jede zustandslose Prüfung — Schutz
 * gegen das Zurückfallen auf die schwächere Herkunftsprüfung. Der Fehler saß im Browser:
 * `csrf_protection_controller.ts` war `stimulusFetch: 'lazy'` und wurde nur geladen, wenn ein
 * Element `data-controller="csrf-protection"` auf der Seite stand. Symfony-Formulare tragen das von
 * selbst, die handgeschriebenen Token-Felder von Anmeldung und Abmeldung nicht — auf einer frisch
 * geladenen Anmeldeseite fehlte das Modul, das den Double-Submit schreibt.
 *
 * Ein Servertest kann das JavaScript nicht ausführen. Geprüft wird deshalb, was der Browser
 * bekommt: dass das Modul auf jeder Seite sofort geladen wird und dass die Felder es binden. Den
 * Ablauf selbst belegt der Browserlauf (`qa-report.md`, Vermerk unter BF-156).
 */
final class Bf156CsrfControllerTest extends AbstractWebTestCase
{
    public function testDerCsrfControllerWirdSofortGeladenNichtVerzoegert(): void
    {
        $client = static::createClient();
        $lader = $client->getContainer()->get(AssetMapperInterface::class)
            ->getAsset('@symfony/stimulus-bundle/controllers.js')?->content ?? '';

        self::assertMatchesRegularExpression('/eagerControllers\s*=\s*\{[^}]*"csrf-protection"/', $lader, 'Lazy fehlte das Modul genau auf den Seiten, die es brauchen.');
        self::assertDoesNotMatchRegularExpression('/lazyControllers\s*=\s*\{[^}]*"csrf-protection"/', $lader);
    }

    public function testAnmeldeformularBindetDenController(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', self::LOCALE.'/login');

        self::assertCount(1, $crawler->filter('input[name="_csrf_token"][data-controller="csrf-protection"]'));
    }

    public function testBeideAbmeldeformulareBindenDenController(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        foreach (['/', '/profile'] as $pfad) {
            $crawler = $client->request('GET', self::LOCALE.$pfad);
            $felder = $crawler->filter('form[action$="/logout"] input[name="_csrf_token"]');

            self::assertGreaterThan(0, $felder->count(), $pfad);
            foreach ($felder as $feld) {
                \assert($feld instanceof \DOMElement);
                self::assertSame('csrf-protection', $feld->getAttribute('data-controller'), $pfad);
            }
        }
    }
}
