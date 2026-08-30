<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Kein Verzeichnis unter `public/` heißt wie eine Route (BF-100).
 *
 * ⚠ **Der Fehler, gegen den dieser Lauf steht, war auf Produktion kritisch und
 * lokal unsichtbar.** Feature 05 legte `public/presse/` an — denselben Namen wie
 * der sprachfreie Kurzlink `/presse`. Auf Apache schickt `mod_dir` jeden Aufruf
 * von `/presse` per **301** auf `/presse/`, weil ein Verzeichnis existiert;
 * Symfonys Trailing-Slash-Regel schickt zurück. Ergebnis: eine endlose
 * Weiterleitungsschleife auf genau der Adresse, die „in Mails an Redaktionen und
 * auf Visitenkarten steht".
 *
 * Der Symfony-Entwicklungsserver hat kein `mod_dir` und liefert Dateien selbst
 * aus — dort lief alles. Drei QA-Durchläufe, ein Codequalitäts-Durchlauf und ein
 * grüner CI-Lauf haben es nicht gesehen, weil die Kollision **nur unter Apache**
 * entsteht. Deshalb prüft dieser Lauf nicht das Verhalten, sondern die Ursache:
 * die Namensgleichheit selbst.
 */
final class RouteDirectoryCollisionTest extends KernelTestCase
{
    /**
     * Verzeichnisse, die der Webserver ohnehin nie an den Front-Controller
     * durchreicht bzw. die nicht im Repository stehen.
     */
    private const array IGNORIERT = ['build', 'bundles', 'assets'];

    public function testKeinVerzeichnisUnterPublicHeisstWieEineRoute(): void
    {
        self::bootKernel();
        $projektVerzeichnis = static::getContainer()->getParameter('kernel.project_dir');
        $router = static::getContainer()->get(RouterInterface::class);

        $verzeichnisse = [];
        foreach (scandir($projektVerzeichnis.'/public') ?: [] as $eintrag) {
            if (!str_starts_with($eintrag, '.') && is_dir($projektVerzeichnis.'/public/'.$eintrag)
                && !\in_array($eintrag, self::IGNORIERT, true)) {
                $verzeichnisse[] = $eintrag;
            }
        }
        self::assertNotEmpty($verzeichnisse, 'Es wurden keine Verzeichnisse gefunden — der Sammler greift ins Leere.');

        $kollisionen = [];
        foreach ($router->getRouteCollection() as $name => $route) {
            // ⚠ Nicht das ganze Segment vergleichen, sondern seinen **statischen
            // Anfang**: `/presse{trailing_slash}` kollidiert mit `public/presse/`
            // genauso wie `/presse` es tat. Wer hier auf `{` prüft und überspringt,
            // baut sich ein Prüfwerkzeug, das den Fehler von gestern nicht mehr
            // fände — genau das ist beim Schreiben dieses Laufs passiert.
            $erstesSegment = explode('/', trim($route->getPath(), '/'))[0] ?? '';
            $statisch = explode('{', $erstesSegment)[0];
            if ('' === $statisch) {
                continue;  // rein dynamisch, etwa /{_locale}/… — kollidiert nie
            }
            if (\in_array($statisch, $verzeichnisse, true)) {
                $kollisionen[] = sprintf('%s (%s) ↔ public/%s/', $name, $route->getPath(), $statisch);
            }
        }

        self::assertSame([], $kollisionen, sprintf(
            "Ein Verzeichnis unter public/ heißt wie eine Route. Auf Apache erzeugt das eine "
            ."endlose Weiterleitungsschleife (mod_dir → /pfad/, Symfony → /pfad), und zwar nur "
            ."dort — der Entwicklungsserver zeigt es nicht:\n  %s",
            implode("\n  ", $kollisionen),
        ));
    }
}
