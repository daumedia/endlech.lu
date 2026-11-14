<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature 17 · Prüfläufe aus der QA vom 2026-11-14 (`features/17-favoriten-und-listen/qa-report.md`).
 *
 * Ergänzt die Prüfläufe des Baus dort, wo die QA ein Kriterium, eine Kehrseite oder einen Angriff
 * geprüft hat, den der Bau nicht abdeckt (AK-06 hatte keinen eigenen Prüflauf, AK-58 nur Einzelfälle,
 * das Feld `ziel` keinen Angriffsfall).
 *
 * Die Reproduktionen BF-211 bis BF-214 waren übersprungen, solange die Befunde offen waren (scharf mit
 * `QA17_REPRO=1`). Seit der Behebung am 2026-11-14 (Fehlerauftrag 17) laufen sie immer — Rückfallschutz.
 */
final class Qa17PruefungTest extends AbstractWebTestCase
{
    use SavedPagesFixtures;

    private const string KONTO = 'user@endlech.lu';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deckelZuruecksetzen();
    }

    private static function offen(string $befund): void
    {
        if ('1' !== getenv('QA17_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/17-favoriten-und-listen/qa-report.md. Scharf mit QA17_REPRO=1.');
        }
    }

    /**
     * AK-06: Ohne JavaScript, Suche auf Seite 2 mit gesetztem Filter, Herz der vierten Karte —
     * danach dieselbe Seite mit demselben Filter, Anker auf diese Karte, Meldung, Herz gefüllt.
     * (Im Browser ohne JavaScript zusätzlich nachgestellt: `qa/17/QA-AK06-ohne-js-seite2-vierte-karte.png`.)
     */
    public function testAk06OhneJavaScriptSeite2MitFilterZurueckZurViertenKarte(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        foreach ($this->neueLokale($client, 6, 'Rampe') as $lokal) {
            $lokal->setIsWheelchairAccessible(true);
        }
        $this->em($client)->flush();

        $suche = self::LOCALE.'/restaurants?wheelchair=1&sort=name&page=2';
        $seite = $client->request('GET', $suche);
        self::assertResponseIsSuccessful();
        $herzen = $seite->filter('form[id^="favorit-"]');
        self::assertGreaterThanOrEqual(4, $herzen->count(), 'Seite 2 braucht mindestens vier Karten.');
        $vierte = $herzen->eq(3);
        $id = (int) substr((string) $vierte->attr('id'), \strlen('favorit-'));

        $client->submit($vierte->form());

        self::assertResponseRedirects($suche.'#favorit-'.$id, 303);
        self::assertSame(1, $this->sql($client, 'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?', [$konto->getId(), $id]));
        $danach = $client->followRedirect();
        self::assertStringContainsString('Gemerkt: ', $danach->text());
        self::assertStringEndsWith('aus Favoriten entfernen', trim($danach->filter('#favorit-knopf-'.$id)->text()));
        self::assertSame('2', $client->getRequest()->query->get('page'));
        self::assertSame('1', $client->getRequest()->query->get('wheelchair'));
    }

    /**
     * AK-58 als Ganzes, gemessen am Kontingent: Was scheitert oder nichts ändert, zählt nicht;
     * Häkchen und neue Liste im selben Absenden zählen genau einmal.
     */
    public function testAk58NurTatsaechlicheAenderungenZaehlenUndEinKombiniertesAbsendenEinmal(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        [$a, $b] = $this->neueLokale($client, 2, 'Deckel');
        $liste = $this->liste($client, $konto, 'Vorhanden');
        $start = $this->deckelRest($client, $konto);

        // Herz setzen: zählt einmal; dasselbe Herz noch einmal (veraltete Seite): zählt nicht.
        $herz = $client->request('GET', self::LOCALE.'/restaurants/'.$a->getId())->filter('#favorit-'.$a->getId())->form();
        $client->submit($herz);
        $client->submit($herz);
        self::assertSame($start - 1, $this->deckelRest($client, $konto));

        // Abgewiesen (leerer Name, Dublette, zu lange Notiz) und wirkungslos (leere Notiz ohne
        // Notiz, unveränderte Häkchen): zählt nicht.
        $client->request('POST', self::LOCALE.'/gespeichert/listen', ['saved_list_name' => ['name' => '  ', '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        $client->request('POST', self::LOCALE.'/gespeichert/listen', ['saved_list_name' => ['name' => 'vorhanden', '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        $client->request('POST', self::LOCALE.'/restaurants/'.$a->getId().'/notiz', ['restaurant_note' => ['text' => str_repeat('x', 501), '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        $client->request('POST', self::LOCALE.'/restaurants/'.$a->getId().'/notiz', ['restaurant_note' => ['text' => '   ', '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        $client->request('POST', self::LOCALE.'/restaurants/'.$a->getId().'/listen', ['restaurant_lists' => ['newList' => '', '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        self::assertSame($start - 1, $this->deckelRest($client, $konto));

        // Häkchen + neue Liste in einem Absenden: genau eins.
        $client->request('POST', self::LOCALE.'/restaurants/'.$b->getId().'/listen', ['restaurant_lists' => ['lists' => [(string) $liste->getId()], 'newList' => 'Kombi', '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        self::assertResponseRedirects(null, 303);
        self::assertSame(2, $this->sql($client, 'SELECT COUNT(*) FROM saved_list_entry WHERE restaurant_id = ?', [$b->getId()]));
        self::assertSame($start - 2, $this->deckelRest($client, $konto));
    }

    /** @return iterable<string, array{string}> */
    public static function fremdeZiele(): iterable
    {
        yield 'protokollrelativ' => ['//evil.example/x'];
        yield 'absolut' => ['https://evil.example/'];
        yield 'Backslash' => ['/\\evil.example'];
        yield 'Zeilenumbruch' => ["/de/restaurants\r\nX-Injected: 1"];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'andere Sprache' => ['/fr/restaurants'];
        yield 'fremde Seite' => ['/de/profile'];
        yield 'POST-Route' => ['/de/restaurants/1/listen'];
    }

    /**
     * Angriff auf das Feld `ziel` (design.md: kein offener Redirect): Jede fremde, kaputte oder nicht
     * zugelassene Adresse führt auf die Detailseite des Lokals, nie nach außen, keine Kopfzeile entsteht.
     */
    #[DataProvider('fremdeZiele')]
    public function testZielIstKeinOffenerRedirectUndKeineKopfzeilenInjektion(string $ziel): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);
        $lokal = $this->neueLokale($client, 1, 'Ziel')[0];
        $form = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId())->filter('#favorit-'.$lokal->getId())->form();
        $form['ziel']->setValue($ziel);

        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/restaurants/'.$lokal->getId().'#favorit-'.$lokal->getId(), 303);
        self::assertFalse($client->getResponse()->headers->has('X-Injected'));
    }

    /**
     * EC-01: Der Merkwunsch lebt nur in der Sitzung des Gastes. Wer sich in einer anderen Sitzung
     * anmeldet (Bestätigungslink im anderen Browser), hat danach nichts gemerkt.
     */
    public function testEc01MerkwunschVerfaelltInEinerAnderenSitzung(): void
    {
        $gast = static::createClient();
        $lokal = $this->neueLokale($gast, 1, 'Merkwunsch')[0];
        $gast->submit($gast->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId())->filter('#favorit-'.$lokal->getId())->form());
        self::assertResponseRedirects(self::LOCALE.'/login', 303);
        self::ensureKernelShutdown();

        $anderer = static::createClient();
        $anmeldung = $anderer->request('GET', self::LOCALE.'/login');
        $anderer->submit($this->formWithField($anmeldung, '_username', ['_username' => self::KONTO, '_password' => 'user123']));

        self::assertSame(0, $this->sql($anderer, 'SELECT COUNT(*) FROM favorite WHERE restaurant_id = ?', [$lokal->getId()]));
    }

    /**
     * BF-211 (Reproduktion, EC-07): Ein Listenname aus „İ“ (U+0130) ist als Name höchstens 60 Zeichen
     * lang, sein Vergleichsschlüssel aber doppelt so lang (`mb_strtolower` → „i“ + U+0307) — die Spalte
     * `name_key` VARCHAR(60) läuft über, SQLSTATE[22001], HTTP 500. Auf allen drei Wegen.
     */
    public function testBf211Ec07ListennameMitTuerkischemIIstNieEinServerfehler(): void
    {
        // Scharf seit der Behebung am 2026-11-14 (Fehlerauftrag 17) — Rückfallschutz.

        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->neueLokale($client, 1, 'Istanbul')[0];
        $liste = $this->liste($client, $konto, 'Umbenennen');
        $name = str_repeat('İ', 31);

        $client->request('POST', self::LOCALE.'/gespeichert/listen', ['saved_list_name' => ['name' => $name, '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        self::assertLessThan(500, $client->getResponse()->getStatusCode(), 'Übersicht');

        $client->request('POST', self::LOCALE.'/restaurants/'.$lokal->getId().'/listen', ['restaurant_lists' => ['newList' => $name.'X', '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        self::assertLessThan(500, $client->getResponse()->getStatusCode(), 'Detailseite');

        $client->request('POST', self::LOCALE.'/gespeichert/listen/'.$liste->getId().'/umbenennen', ['saved_list_name' => ['name' => $name.'Y', '_token' => 'csrf-token']], [], ['HTTP_REFERER' => 'http://localhost/']);
        self::assertLessThan(500, $client->getResponse()->getStatusCode(), 'Umbenennen');
    }

    /**
     * BF-212 (Reproduktion): Eine Listennummer jenseits von PHP_INT_MAX passt auf `\d+`, scheitert aber
     * an `int $id` — TypeError, HTTP 500 statt 404 wie bei jeder anderen unbekannten Liste (AK-53).
     */
    public function testBf212ListennummerJenseitsVonPhpIntMaxIst404(): void
    {
        // Scharf seit der Behebung am 2026-11-14 (Fehlerauftrag 17) — Rückfallschutz.

        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        foreach (['/gespeichert/listen/99999999999999999999', '/gespeichert/listen/99999999999999999999/loeschen'] as $pfad) {
            $client->request('GET', self::LOCALE.$pfad);
            self::assertResponseStatusCodeSame(404, $pfad);
        }
    }

    /**
     * BF-213 (Reproduktion): Das Feld `page` im Formular „Aus Liste entfernen“ wird nur nach unten
     * begrenzt; eine riesige Seitenzahl läuft in `(seite - 1) * 12` über, TypeError, HTTP 500.
     */
    public function testBf213RiesigeSeiteBeimEntfernenIstKeinServerfehler(): void
    {
        // Scharf seit der Behebung am 2026-11-14 (Fehlerauftrag 17) — Rückfallschutz.

        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->neueLokale($client, 1, 'Seite')[0];
        $liste = $this->liste($client, $konto, 'Seitenzahl');
        $this->eintraege($client, $liste, [$lokal]);
        $form = $client->request('GET', self::LOCALE.'/gespeichert/listen/'.$liste->getId())->filter('form[action$="/entfernen"]')->form();
        $form['page']->setValue('9223372036854775807');

        $client->submit($form);

        self::assertLessThan(500, $client->getResponse()->getStatusCode());
    }

    /**
     * BF-214 (Reproduktion, vorbestehend in B05, gefunden beim Vergleich für EC-16): Die Suche mit
     * `?page=PHP_INT_MAX` läuft in `setFirstResult()` über — TypeError, HTTP 500. „Favoriten“ und
     * Listen antworten an derselben Stelle mit 404.
     */
    public function testBf214SucheMitSeitePhpIntMaxIstKeinServerfehler(): void
    {
        // Scharf seit der Behebung am 2026-11-14 (Fehlerauftrag 17) — Rückfallschutz.

        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/restaurants?page=9223372036854775807');

        self::assertResponseStatusCodeSame(404);
    }
}
