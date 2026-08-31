<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Repository\BoardIdeaRepository;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * QA 06 · Angriffsdurchlauf, Abschnitt 7 (Eingaben).
 *
 * Pro Feld: leer, ein Zeichen, sehr lang, Emoji, SQL, Skript, Pfadwechsel.
 * Erwartet wird jeweils eine saubere Ablehnung **oder** eine sichere
 * Verarbeitung — nie ein Serverfehler und nie eine ausgeführte Eingabe.
 */
final class BoardInputHardeningTest extends AbstractWebTestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string, 2: bool}>
     */
    public static function eingaben(): iterable
    {
        //                             Titel                          Beschreibung        angenommen?
        yield 'leer'            => ['',                               '',                  false];
        yield 'ein Zeichen'     => ['a',                              'b',                 true];
        yield 'Titel 10000'     => [str_repeat('x', 10000),           'ok',                false];
        yield 'Text 10000'      => ['ok',                             str_repeat('x', 10000), false];
        yield 'nur Emoji'       => ['🦽♿️🧏',                          '🦽 Beschreibung',    true];
        yield 'SQL'             => ["'; DROP TABLE board_idea; --",   "1' OR '1'='1",      true];
        yield 'Skript'          => ['<script>alert(1)</script>',      '<img src=x onerror=alert(1)>', true];
        yield 'Pfadwechsel'     => ['../../etc/passwd',               '../../../etc/shadow', true];
        yield 'Nullbyte'        => ["Titel\x00abgeschnitten",         'ok',                true];
        // EC-03: Der Slugger dehnt aus — 120 japanische Zeichen ergeben bis zu
        // 360 Slug-Zeichen bei einer Spalte von 160.
        yield 'EC-03 japanisch' => [str_repeat('日', 120),             'Ausdehnung im Slug', true];
        yield 'EC-03 scharfes s'=> [str_repeat('ß', 120),             'Ausdehnung im Slug', true];
    }

    #[DataProvider('eingaben')]
    public function testEingabeErzeugtNieEinenServerfehler(string $titel, string $text, bool $angenommen): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen/neu');
        $form = $this->formWithField($crawler, 'board_idea[title]', [
            'board_idea[title]' => $titel,
            'board_idea[description]' => $text,
        ]);
        $client->submit($form);

        $code = $client->getResponse()->getStatusCode();
        self::assertLessThan(500, $code, "Eingabe erzeugte HTTP {$code} — ein Serverfehler ist nie die richtige Antwort.");

        if ($angenommen) {
            self::assertSame(Response::HTTP_FOUND, $code, 'Erwartet: angenommen.');
            $ideen = $client->getContainer()->get(BoardIdeaRepository::class)->findAll();
            self::assertCount(1, $ideen);
            // Der Slug bleibt in der Spaltenbreite, egal wie stark der Slugger ausdehnt.
            self::assertLessThanOrEqual(160, mb_strlen($ideen[0]->getSlug()));
        } else {
            self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $code, 'Erwartet: abgewiesen mit Meldung.');
            self::assertSame(0, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
        }
    }

    /** Die Tabelle existiert nach dem SQL-Versuch noch. */
    public function testTabelleUeberlebtDenSqlVersuch(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen/neu');
        $client->submit($this->formWithField($crawler, 'board_idea[title]', [
            'board_idea[title]' => "'; DROP TABLE board_idea; --",
            'board_idea[description]' => "x'; DELETE FROM board_vote; --",
        ]));

        $anzahl = $client->getContainer()->get(BoardIdeaRepository::class)->count([]);
        self::assertSame(1, $anzahl, 'Die Tabelle steht noch und enthält den Eintrag als Text.');
    }
}
