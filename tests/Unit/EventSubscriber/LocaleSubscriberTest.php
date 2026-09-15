<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Sitzungslose Routen bleiben sitzungslos — auch in der Unteranfrage ihrer Fehlerseite
 * (Feature 10, Selbsttest).
 *
 * Die Anfragen hier tragen absichtlich KEINE Sitzung: Fasst der Abonnent sie an, wirft
 * `getSession()` eine `SessionNotFoundException`. Genau das ist die Messung.
 */
final class LocaleSubscriberTest extends TestCase
{
    private function unteranfrageNach(Request $haupt): array
    {
        $stack = new RequestStack();
        $stack->push($haupt);
        // So baut Symfony die Fehler-Unteranfrage: neue Attribute, `_stateless` fehlt.
        $unter = $haupt->duplicate(null, null, ['_controller' => 'error_controller', '_locale' => 'de']);
        $stack->push($unter);

        return [new LocaleSubscriber($stack, ['lb', 'de', 'fr', 'en', 'pt']), new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $unter,
            HttpKernelInterface::SUB_REQUEST,
        )];
    }

    public function testFehlerseiteEinerZustandslosenRouteFasstKeineSitzungAn(): void
    {
        $haupt = Request::create('/sitemap.xml');
        $haupt->attributes->set('_stateless', true);
        [$abonnent, $ereignis] = $this->unteranfrageNach($haupt);

        $abonnent->onKernelRequest($ereignis);

        self::assertFalse($ereignis->getRequest()->hasSession(), 'Es darf keine Sitzung entstanden sein.');
    }

    /** Gegenprobe: Bei einer gewöhnlichen Seite liest die Fehlerseite die Sprache wie bisher aus der Sitzung. */
    public function testFehlerseiteEinerGewoehnlichenSeiteBrauchtDieSitzungWeiterhin(): void
    {
        [$abonnent, $ereignis] = $this->unteranfrageNach(Request::create('/de/restaurants/999999'));

        $this->expectException(SessionNotFoundException::class);
        $abonnent->onKernelRequest($ereignis);
    }

    public function testZustandsloseHauptanfrageBleibtSitzungslos(): void
    {
        $haupt = Request::create('/health');
        $haupt->attributes->set('_stateless', true);
        $stack = new RequestStack();
        $stack->push($haupt);

        (new LocaleSubscriber($stack, ['lb', 'de', 'fr', 'en', 'pt']))->onKernelRequest(new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $haupt,
            HttpKernelInterface::MAIN_REQUEST,
        ));

        self::assertFalse($haupt->hasSession());
    }
}
