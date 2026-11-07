<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Board\BoardModerator;
use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

/**
 * Feature 16 · Prüfläufe aus der zweiten QA vom 2026-11-07 (`features/16-roadmap-und-ideen/qa-report.md`,
 * Abschnitt „Zweiter Durchlauf“).
 *
 * Die Reproduktionen von BF-206, BF-207 und BF-208 sind übersprungen, solange die Befunde offen sind;
 * scharf mit `QA16_REPRO=1`. Die übrigen Fälle sichern Behebungen aus dem Fehlerauftrag gegen Rückfall,
 * die der Bau nur im Browser oder gar nicht abgesichert hat.
 */
final class Qa16NachpruefungTest extends AbstractWebTestCase
{
    use MailerAssertionsTrait;

    private static function offen(string $befund): void
    {
        if ('1' !== getenv('QA16_REPRO')) {
            self::markTestSkipped($befund . ' offen — Reproduktion, siehe features/16-roadmap-und-ideen/qa-report.md. Scharf mit QA16_REPRO=1.');
        }
    }

    private function idee(KernelBrowser $client, string $titel, string $slug, ?User $verfasser, bool $veroeffentlicht, string $locale = 'de'): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung QA16 Nachprüfung ' . $titel)
            ->setSlug($slug)
            ->setLocale($locale)
            ->setStatus(BoardIdeaStatus::NEW)
            ->setSubmittedBy($verfasser);

        if ($veroeffentlicht) {
            $idee->setPublishedAt(new \DateTimeImmutable('-1 day'));
        }

        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    /**
     * BF-199 (Kehrseite der Behebung): Ein **bestätigtes** fremdes Konto — nicht nur ein unbestätigtes oder
     * der Admin — bekommt beim Zustimmen auf eine wartende Idee 404 ohne `Location`, mit leerem, falschem
     * und sogar mit einem für diese Idee gültig aussehenden Token. Die Zustimmung zu einer veröffentlichten
     * Idee mit falschem Token leitet dagegen weiter wie bisher (Bestandsverhalten, kein Titel einer
     * wartenden Idee).
     */
    public function testBf199BestaetigtesFremdesKontoErfaehrtNichtsUeberWartendeIdee(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'admin@endlech.lu');
        $wartend = $this->idee($client, 'QA16N Seidenreiher in der Warteschlange', 'qa16n-seidenreiher-in-der-warteschlange', $verfasser, false);
        $oeffentlich = $this->idee($client, 'QA16N Öffentliche Idee', 'qa16n-oeffentliche-idee', $verfasser, true);

        $this->loginAs($client, 'user@endlech.lu');
        foreach (['', 'falsch', str_repeat('a', 43)] as $token) {
            $client->request('POST', sprintf('/de/roadmap/ideen/%d/zustimmen', $wartend->getId()), ['_token' => $token]);
            self::assertResponseStatusCodeSame(404, sprintf('Token „%s“', $token));
            self::assertNull($client->getResponse()->headers->get('Location'));
            self::assertStringNotContainsStringIgnoringCase('seidenreiher', (string) $client->getResponse()->getContent());
        }

        // Kehrseite der neuen Reihenfolge: Die CSRF-Prüfung greift weiterhin — eine veröffentlichte Idee mit
        // falschem Token führt zurück auf ihre Seite, mit Meldung, und es zählt KEINE Stimme. (Aus dem
        // code-reviewer der zweiten QA: Vorher prüfte kein Lauf, dass ein falscher Token abgewiesen wird;
        // gegengeprüft mit auskommentierter CSRF-Zeile → rot.)
        $client->request('POST', sprintf('/de/roadmap/ideen/%d/zustimmen', $oeffentlich->getId()), ['_token' => 'falsch']);
        self::assertResponseRedirects(sprintf('/de/roadmap/ideen/%d-qa16n-oeffentliche-idee', $oeffentlich->getId()));
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Ungültiges CSRF-Token. Bitte versuche es erneut.');
        $stimmen = $client->getContainer()->get(\App\Repository\BoardIdeaRepository::class)
            ->countVotesFor([(int) $oeffentlich->getId()])[(int) $oeffentlich->getId()] ?? 0;
        self::assertSame(0, $stimmen, 'Ein falscher Token darf keine Stimme zählen.');
    }

    /**
     * BF-200 (Rückfallschutz): Über der Reiterleiste steht auf allen vier Seitenarten **dasselbe Markup** —
     * dann steht die Leiste überall an derselben Stelle (AK-05). Gemessen im Browser: 173 px bei 1280,
     * 165 px bei 375 in allen fünf Sprachen (`qa/16/QA2-AK-05-…`). Dieser Lauf vergleicht das Band
     * byte-genau, auch in der Sprache mit dem längsten Knopf (fr) und auf einer Folgeseite.
     */
    #[DataProvider('sprachen')]
    public function testAk05BandUeberDenReiternIstAufAllenSeitenartenGleich(string $sprache): void
    {
        $client = static::createClient();
        $idee = $this->idee($client, 'QA16N Kopf', 'qa16n-kopf', null, true);

        $baender = [];
        foreach (["/$sprache/roadmap", "/$sprache/roadmap/ideen", "/$sprache/roadmap/ideen?page=2&sort=newest", "/$sprache/changelog", "/$sprache/roadmap/ideen/{$idee->getId()}-qa16n-kopf"] as $pfad) {
            $crawler = $client->request('GET', $pfad);
            self::assertResponseIsSuccessful($pfad);
            $baender[$pfad] = preg_replace('/\s+/', ' ', $crawler->filter('[data-area-band]')->outerHtml());
            self::assertCount(1, $crawler->filter('[data-area-header] > [data-area-band] + [data-area-tabs]'), $pfad . ': Leiste direkt unter dem Band');
        }

        self::assertCount(1, array_unique($baender), 'AK-05: Das Band über der Reiterleiste unterscheidet sich je Seitenart.');
    }

    /**
     * AK-37 (Lücke aus BF-210): Die Überschrift der Community-Gruppe auf dem Reiter Roadmap sagt in keiner
     * Sprache „Board“. `RoadmapAreaLocaleTest::testAk37…` liest dafür das ERSTE `h3` der Spalte „Geplant“ —
     * das ist ein kuratiertes Vorhaben, nicht die Gruppenüberschrift; mit fest eingetragenem „Ideen-Board“ im
     * Template blieb die ganze Suite grün. Dieser Lauf greift die Gruppe selbst (Trennlinie über ihr) und
     * prüft auch den Verweis an jeder Idee und den Hinweis „N weitere“.
     */
    #[DataProvider('sprachen')]
    public function testAk37GruppenueberschriftUndVerweiseOhneBoard(string $sprache): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        for ($i = 1; $i <= 11; ++$i) {
            $idee = $this->idee($client, 'QA16N Geplant ' . $i, 'qa16n-geplant-' . $i, null, true);
            $idee->setStatus(BoardIdeaStatus::PLANNED);
        }
        $em->flush();
        $client->getContainer()->get(\App\Roadmap\CommunityRoadmap::class)->invalidate();

        $crawler = $client->request('GET', "/$sprache/roadmap");
        self::assertResponseIsSuccessful();
        $gruppe = $crawler->filter('section[aria-labelledby="stage-planned"] div.border-t');
        self::assertCount(1, $gruppe, 'Community-Gruppe vorhanden');

        $texte = [$gruppe->filter('h3')->text()];
        foreach ($gruppe->filter('a') as $a) {
            $texte[] = trim($a->textContent);
        }
        self::assertGreaterThanOrEqual(12, \count($texte), 'Überschrift, zehn Verweise, Hinweis „N weitere“');
        foreach ($texte as $text) {
            self::assertDoesNotMatchRegularExpression('/board|tableau|quadro|ideeën/i', $text, "[$sprache] $text");
        }
    }

    /**
     * BF-207 (Reproduktion): Die Einzelseite einer Idee hat keine Hauptüberschrift (`h1`). axe-core meldet
     * dort `page-has-heading-one` — in allen fünf Sprachen und bei beiden Breiten; AK-28 verlangt null
     * Verstöße auch für die Einzelseite (OF-02 nimmt nur die Wortmarke aus). Der Titel der Idee steht als
     * `h3` in der Karte.
     */
    #[DataProvider('sprachen')]
    public function testBf207EinzelseiteHatGenauEineHauptueberschrift(string $sprache): void
    {
        self::offen('BF-207');

        $client = static::createClient();
        $idee = $this->idee($client, 'QA16N Einzelseite', 'qa16n-einzelseite', null, true, $sprache);

        $crawler = $client->request('GET', sprintf('/%s/roadmap/ideen/%d-qa16n-einzelseite', $sprache, $idee->getId()));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('h1'), 'AK-28 (axe page-has-heading-one): genau eine h1 auf der Einzelseite.');
    }

    /**
     * BF-206 (Reproduktion): Der globale Fokus-Fallback in `assets/styles/app.css` steht außerhalb jeder
     * Kaskadenebene. Ungeschichtete Deklarationen schlagen Tailwinds `@layer utilities` unabhängig von der
     * Spezifität — `focus:outline-white` (und jede andere `focus:outline-<farbe>`) ist dadurch wirkungslos;
     * auf dem Verlaufsband des Bereichskopfs erscheint cyan-700 mit 1,5–1,65 : 1 statt Weiß
     * (`qa/16/QA2-BF-206-…`). Der Lauf verlangt, dass die Regel in einer Kaskadenebene liegt.
     *
     * Wer BF-206 anders behebt (etwa über einen Selektor, der Elemente mit eigener Fokusfarbe ausnimmt),
     * passt diesen Lauf an — maßgeblich ist die Browsermessung, nicht diese Form.
     */
    public function testBf206GlobalerFokusFallbackLiegtInEinerKaskadenebene(): void
    {
        self::offen('BF-206');

        $css = (string) file_get_contents(\dirname(__DIR__, 3) . '/assets/styles/app.css');
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);

        $stapel = [];
        $kopf = '';
        $gefunden = null;
        for ($i = 0, $n = \strlen($css); $i < $n; ++$i) {
            $z = $css[$i];
            if ('{' === $z) {
                $stapel[] = trim($kopf);
                if (':focus-visible' === trim($kopf)) {
                    $rumpf = substr($css, $i, (int) strpos($css, '}', $i) - $i);
                    if (str_contains($rumpf, 'outline')) {
                        $gefunden = $stapel;
                    }
                }
                $kopf = '';
            } elseif ('}' === $z) {
                array_pop($stapel);
                $kopf = '';
            } elseif (';' === $z) {
                $kopf = '';
            } else {
                $kopf .= $z;
            }
        }

        self::assertNotNull($gefunden, 'Die globale :focus-visible-Regel mit outline wurde nicht gefunden.');
        $ebenen = array_filter($gefunden, static fn (string $k): bool => str_starts_with($k, '@layer'));
        self::assertNotEmpty($ebenen, 'BF-206: :focus-visible { outline … } steht ungeschichtet und übersteuert jede focus:outline-<farbe>.');
    }

    /**
     * BF-208 (Reproduktion): Der Textteil der Freigabe-Mail trägt HTML-Entitäten. Die Mail hat keine
     * Textvorlage; Symfony bildet den Textteil per `strip_tags` aus dem HTML, ohne Entitäten aufzulösen.
     * Seit dem Fehlerauftrag (BF-201) steht „Roadmap &amp; Ideen“ im Klartext; französische Mails trugen
     * schon vorher `&#039;` (vorbestehend, auf `main` nachgestellt).
     */
    #[DataProvider('sprachen')]
    public function testBf208TextteilDerFreigabeMailOhneHtmlEntitaeten(string $sprache): void
    {
        self::offen('BF-208');

        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, 'QA16N Mailtext ' . $sprache, 'qa16n-mailtext-' . $sprache, $verfasser, false, $sprache);
        $client->getContainer()->get(BoardModerator::class)->publish($idee);

        $mail = self::getMailerMessage();
        self::assertNotNull($mail);
        $text = (string) $mail->getTextBody();
        self::assertNotSame('', $text, 'Die Mail hat einen Textteil.');
        self::assertDoesNotMatchRegularExpression('/&(#\d+|#x[0-9a-f]+|[a-z]+);/i', $text, 'BF-208: HTML-Entität im Textteil.');
    }

    /**
     * BF-209 (Reproduktion): Ist das Original einer Dublette eine **wartende** Idee, leitet die Einzelseite der
     * Dublette jeden Gast auf die Adresse des Originals weiter — mit dessen Titelteil im `Location`-Kopf, auch
     * über die alte Adresse. `show()` prüft die Sichtbarkeit erst nach der Dubletten-Weiterleitung, und
     * `AdminBoardController::merge()` nimmt jedes Ziel an (die Auswahl im Formular bietet nur veröffentlichte,
     * ein handgemachter POST jedes). Vorbestehend aus Feature 06 — dieselbe Fehlerklasse wie BF-199.
     */
    public function testBf209DubletteVerraetWartendesOriginalNicht(): void
    {
        self::offen('BF-209');

        $client = static::createClient();
        $original = $this->idee($client, 'QA16N Wartendes Original Pfauenauge', 'qa16n-wartendes-original-pfauenauge', $this->user($client, 'user@endlech.lu'), false);
        $dublette = $this->idee($client, 'QA16N Dublette', 'qa16n-dublette', null, true);
        $dublette->setDuplicateOf($original);
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        foreach ([sprintf('/de/roadmap/ideen/%d-qa16n-dublette', $dublette->getId()), sprintf('/fr/roadmap/ideen/%d-x', $dublette->getId())] as $pfad) {
            $client->request('GET', $pfad);
            self::assertStringNotContainsString('pfauenauge', (string) $client->getResponse()->headers->get('Location'), $pfad);
            self::assertStringNotContainsStringIgnoringCase('pfauenauge', (string) $client->getResponse()->getContent(), $pfad);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }
}
