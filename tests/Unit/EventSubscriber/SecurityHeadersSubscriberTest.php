<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SecurityHeadersSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class SecurityHeadersSubscriberTest extends TestCase
{
    public function testDokumentBekommtAlleKopfzeilen(): void
    {
        $antwort = $this->schicke(new Response('<html></html>'));

        self::assertSame('nosniff', $antwort->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $antwort->headers->get('X-Frame-Options'));
        self::assertSame('strict-origin-when-cross-origin', $antwort->headers->get('Referrer-Policy'));
        self::assertNotNull($antwort->headers->get('Permissions-Policy'));
        self::assertStringContainsString("frame-ancestors 'none'", (string) $antwort->headers->get('Content-Security-Policy-Report-Only'));
    }

    /**
     * ⚠ Die CSP geht **Report-Only** hinaus. Eine scharfe Richtlinie, die
     * irgendwo zu eng ist, nimmt der Seite das JavaScript — Passkey-Knopf,
     * Wizard und Turbo wären tot, und es sähe nach einem Frontend-Fehler aus.
     * Wer sie scharf schaltet, tut das bewusst und nach einem Blick in die
     * Browser-Konsole; dieser Prüflauf hält fest, dass es bis dahin nicht
     * versehentlich passiert.
     */
    public function testCspIstReportOnlyUndNichtScharf(): void
    {
        $antwort = $this->schicke(new Response('<html></html>'));

        self::assertTrue($antwort->headers->has('Content-Security-Policy-Report-Only'));
        self::assertFalse($antwort->headers->has('Content-Security-Policy'));
    }

    /**
     * Feature 11, AK-24 · Zählskript und Zählaufrufe laufen über endlech.lu selbst — die Richtlinie
     * bleibt deshalb bei `'self'`. Wer hier eine Fremddomain einträgt, um ein Skript von einem
     * anderen Host zu laden, legt den Standort des zweiten VPS offen, den die Weiterleitung
     * verbergen soll (Entwurf, Entscheidung 1).
     */
    public function testSkriptUndVerbindungenNurVonDerEigenenHerkunft(): void
    {
        $csp = (string) $this->schicke(new Response('<html></html>'))->headers->get('Content-Security-Policy-Report-Only');

        self::assertMatchesRegularExpression("/(^|; )script-src 'self';/", $csp);
        self::assertMatchesRegularExpression("/(^|; )connect-src 'self';/", $csp);
        self::assertStringNotContainsString('http', $csp);
    }

    /**
     * JSON braucht weder Rahmen- noch Inhaltsrichtlinie — `nosniff` dagegen
     * sehr wohl, gerade bei den offenen Daten-Endpunkten.
     */
    public function testJsonBekommtNurDieAllgemeinenKopfzeilen(): void
    {
        $antwort = $this->schicke(new JsonResponse(['a' => 1]));

        self::assertSame('nosniff', $antwort->headers->get('X-Content-Type-Options'));
        self::assertFalse($antwort->headers->has('X-Frame-Options'));
        self::assertFalse($antwort->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function testVorhandeneKopfzeileWirdNichtUeberschrieben(): void
    {
        $antwort = new Response('<html></html>');
        $antwort->headers->set('X-Frame-Options', 'SAMEORIGIN');

        self::assertSame('SAMEORIGIN', $this->schicke($antwort)->headers->get('X-Frame-Options'));
    }

    public function testXPoweredByWirdEntfernt(): void
    {
        $antwort = new Response('<html></html>');
        $antwort->headers->set('X-Powered-By', 'PHP/8.4.25');

        self::assertFalse($this->schicke($antwort)->headers->has('X-Powered-By'));
    }

    /**
     * ⚠ HSTS über `http://` wäre nach RFC 6797 zu verwerfen, und im
     * Debug-Betrieb zwingt sie den Browser dauerhaft auf `https://localhost` —
     * das hält sich, bis jemand die Herkunft von Hand löscht.
     */
    #[DataProvider('hstsFaelle')]
    public function testHstsNurUeberHttpsUndAusserhalbDesDebugBetriebs(bool $debug, bool $sicher, bool $erwartet): void
    {
        $antwort = $this->schicke(new Response('<html></html>'), $debug, $sicher);

        self::assertSame($erwartet, $antwort->headers->has('Strict-Transport-Security'));
    }

    /**
     * @return iterable<string, array{bool, bool, bool}>
     */
    public static function hstsFaelle(): iterable
    {
        yield 'Produktion über https' => [false, true, true];
        yield 'Produktion über http' => [false, false, false];
        yield 'Debug über https' => [true, true, false];
        yield 'Debug über http' => [true, false, false];
    }

    /**
     * ⚠ Eine Unteranfrage darf die Kopfzeilen nicht setzen — sie landeten sonst
     * an einer Antwort, die nie hinausgeht, und die echte bliebe ohne.
     */
    public function testUnteranfrageBleibtUnberuehrt(): void
    {
        $antwort = new Response('<html></html>');
        $this->schicke($antwort, false, true, HttpKernelInterface::SUB_REQUEST);

        self::assertFalse($antwort->headers->has('X-Frame-Options'));
    }

    private function schicke(
        Response $antwort,
        bool $debug = false,
        bool $sicher = true,
        int $typ = HttpKernelInterface::MAIN_REQUEST,
    ): Response {
        $anfrage = Request::create($sicher ? 'https://endlech.lu/de/' : 'http://endlech.lu/de/');

        $ereignis = new ResponseEvent(
            $this->createStub(KernelInterface::class),
            $anfrage,
            $typ,
            $antwort,
        );

        (new SecurityHeadersSubscriber($debug))->onKernelResponse($ereignis);

        return $ereignis->getResponse();
    }
}
