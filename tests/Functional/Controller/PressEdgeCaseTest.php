<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Press\PressPackage;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Randfälle und Querschnittsregeln der Presseseite (Feature 05).
 *
 * Deckt die Fälle ab, die man einer Seite nicht ansieht: dass sie keinen fremden
 * Server kontaktiert, dass sie ohne JavaScript vollständig ist, dass sie im Druck
 * lesbar bleibt und dass ihre Überschriften keine Ebene überspringen.
 */
final class PressEdgeCaseTest extends AbstractWebTestCase
{
    /**
     * AK-38: Kein fremder Server wird kontaktiert — die IP des Besuchers erreicht
     * keinen Dritten.
     *
     * ⚠ Geprüft werden nur Ressourcen, die der Browser **von sich aus** lädt
     * (`img`, `script`, `link`, `iframe`, `source`). Ein `<a href>` auf eine
     * fremde Adresse ist unkritisch: Er wird erst beim Klick geladen, und dann
     * hat der Besucher sich dafür entschieden.
     */
    public function testKeineFremdeRessourceWirdGeladen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');

        $eigenerHost = $client->getRequest()->getHost();
        $fremde = [];
        foreach ([['img', 'src'], ['script', 'src'], ['link', 'href'], ['iframe', 'src'], ['source', 'src']] as [$tag, $attr]) {
            foreach ($crawler->filter($tag) as $knoten) {
                $wert = $knoten->getAttribute($attr);
                if ('' === $wert) {
                    continue;
                }

                $host = parse_url($wert, \PHP_URL_HOST);
                // Relativ oder eigener Host = keine fremde Verbindung. Die
                // Sprachverweise und die kanonische Adresse stehen absolut und
                // zeigen auf uns selbst — sie laden ohnehin nichts.
                if (null === $host || false === $host || $host === $eigenerHost) {
                    continue;
                }

                $fremde[] = $tag.'['.$attr.']='.$wert;
            }
        }

        self::assertSame([], $fremde, 'Die Seite lädt Ressourcen von fremden Servern: '.implode(', ', $fremde));
    }

    /**
     * EC-01: Die Seite ist ohne JavaScript vollständig.
     *
     * Nachgewiesen als Abwesenheit jeder Stimulus-Anbindung im Hauptbereich: Was
     * kein Skript braucht, funktioniert ohne. Der Beschreibungstext steht damit
     * offen im Markup und lässt sich markieren (AK-09).
     */
    public function testDieSeiteBrauchtKeinJavaScript(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');
        $main = $crawler->filter('main');

        self::assertCount(0, $main->filter('[data-controller]'), 'Ein Teil der Seite hängt an einem Stimulus-Controller.');
        self::assertCount(0, $main->filter('[data-action]'), 'Ein Bedienelement hängt an einem JavaScript-Ereignis.');
        self::assertCount(0, $main->filter('script'), 'Im Hauptbereich steht ein Skript.');
        self::assertCount(0, $main->filter('details'), 'Ein Inhalt steckt hinter einem Aufklappelement (AK-09).');
    }

    /**
     * EC-05: Im Druck bleibt das Kopfband lesbar.
     *
     * Der `@media print`-Block in assets/styles/app.css greift auf den Selektor
     * `section.bg-linear-to-r`. Als `<div>` gebaut, druckte das Band weiß auf
     * weiß — der Fehler, den Feature 03 an derselben Stelle hatte.
     */
    public function testDasKopfbandIstEinSectionUndBleibtImDruckLesbar(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');

        self::assertGreaterThan(
            0,
            $crawler->filter('section.bg-linear-to-r')->count(),
            'Das Kopfband ist kein <section> — die Druckregel greift nicht.',
        );
    }

    /** AK-34: Die Überschriften überspringen keine Ebene. */
    public function testDieUeberschriftenSpringenNicht(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');

        $ebenen = [];
        foreach ($crawler->filter('main h1, main h2, main h3, main h4') as $knoten) {
            $ebenen[] = (int) substr($knoten->nodeName, 1);
        }

        self::assertNotEmpty($ebenen);
        self::assertSame(1, $ebenen[0], 'Die erste Überschrift im Hauptbereich ist keine erste Ebene.');

        $vorher = $ebenen[0];
        foreach ($ebenen as $ebene) {
            self::assertLessThanOrEqual($vorher + 1, $ebene, sprintf(
                'Sprung von Ebene %d auf %d — dazwischen fehlt eine Überschrift.', $vorher, $ebene,
            ));
            $vorher = $ebene;
        }
    }

    /** AK-33 (Teil): Jede Aktion trägt eine Mindesthöhe als Tap-Target. */
    public function testJedeAktionTraegtEineMindesthoehe(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');

        $ohne = [];
        foreach ($crawler->filter('main a[href], main button') as $knoten) {
            $klassen = $knoten->getAttribute('class');
            // Verweise im Fließtext sind keine Schaltflächen; geprüft werden die,
            // die als Aktion gestaltet sind (Rahmen, Fläche oder Sprungmarke).
            if (!str_contains($klassen, 'inline-flex')) {
                continue;
            }
            if (!str_contains($klassen, 'min-h-[48px]') && !str_contains($klassen, 'min-h-[44px]')) {
                $ohne[] = trim($knoten->textContent);
            }
        }

        self::assertSame([], $ohne, 'Diese Aktionen haben kein Tap-Target-Maß: '.implode(' · ', $ohne));
    }

    /**
     * EC-04: Fehlt die Paketdatei, steht der Kontaktweg an der Stelle des Knopfes.
     *
     * Der Test prüft beide Seiten derselben Medaille und ist damit unabhängig
     * davon, ob VB-01 schon erfüllt ist: Entweder es gibt einen Download **oder**
     * einen Hinweis — aber nie einen toten Link und nie eine leere Stelle.
     */
    public function testOhnePaketStehtDerKontaktwegAnSeinerStelle(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/presse');
        $material = $crawler->filter('section#material');

        $download = $material->filter('a[download]');

        if (static::getContainer()->get(PressPackage::class)->exists()) {
            self::assertCount(1, $download, 'Das Paket liegt vor, aber es gibt keinen Downloadlink.');
            self::assertStringContainsString('ZIP', $download->text(), 'Der Linktext nennt das Format nicht (AK-20).');

            return;
        }

        self::assertCount(0, $download, 'Es gibt einen Downloadlink auf ein Paket, das nicht existiert.');
        self::assertStringContainsString('mailto:', $material->html(), 'Ohne Paket fehlt der Kontaktweg als Ersatz.');
    }

    /** EC-06/EC-10: Die Vorschauen tragen eine Bezeichnung, nicht ihren Dateinamen (AK-35). */
    #[DataProvider('sprachen')]
    public function testJedeVorschauTraegtEineBezeichnungAlsAlternativtext(string $locale): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/'.$locale.'/presse');

        $bilder = $crawler->filter('section#material img');
        self::assertGreaterThan(0, $bilder->count(), 'Es gibt keine Vorschauen.');

        foreach ($bilder as $bild) {
            $alt = trim($bild->getAttribute('alt'));
            self::assertNotSame('', $alt, 'Eine Vorschau hat keinen Alternativtext.');
            self::assertStringNotContainsString('.svg', $alt, 'Der Alternativtext ist ein Dateiname.');
            self::assertStringNotContainsString('.jpg', $alt, 'Der Alternativtext ist ein Dateiname.');
        }
    }

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en'] as $locale) {
            yield $locale => [$locale];
        }
    }
}
