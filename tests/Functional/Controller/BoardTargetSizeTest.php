<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoardIdea;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Regressionsschutz für BF-104 (QA 06², 2026-08-30).
 *
 * **Der Fehler:** Der Titel-Verweis auf dem Board war eine nackte Textzeile —
 * im Browser **18 px hoch** — und zugleich der einzige Weg in die
 * Einzelansicht. AK-47 verlangt 44 × 44, WCAG 2.2 AA (2.5.8) mindestens
 * 24 × 24.
 *
 * ⚠ **Dieser Prüflauf misst nicht, er liest das Markup.** Die tatsächliche
 * Größe lässt sich nur im Browser messen. Was er verlässlich fängt, ist das
 * Entfernen einer der Klassen, die zusammen die Zielgröße ergeben.
 *
 * ⚠ **Und genau daran ist er schon einmal vorbeigelaufen:** Nach der ersten
 * Reparatur stand `min-h-[44px]` im Markup — der Prüflauf war grün, und das Ziel
 * war trotzdem **36 px breit** (BF-107). Eine Klasse zu bestätigen ist nicht
 * dasselbe wie ihre Wirkung. Deshalb prüft er jetzt **alle vier** Klassen, die
 * zusammen wirken, und die Messung im Browser bleibt Sache der QA:
 *
 * | Klasse | wofür |
 * |---|---|
 * | `min-h-[44px]` | Höhe des Ziels |
 * | `w-full` | Breite — der Verweis füllt die Zeile |
 * | `flex` | ohne Blockdarstellung wirkt die Mindesthöhe nicht |
 * | `basis-full` an der Überschrift | unter `sm:` die volle Zeile, sonst schrumpft der Titel neben dem Statusabzeichen auf 37 px |
 */
final class BoardTargetSizeTest extends AbstractWebTestCase
{
    public function testBF104_TitelverweisTraegtEineZielgroesse(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $idee = (new BoardIdea())
            ->setTitle('Kartenansicht mit Filtern')
            ->setDescription('Beschreibung.')
            ->setSlug('kartenansicht')
            ->setLocale('de');
        $idee->setPublishedAt(new \DateTimeImmutable());
        $em->persist($idee);
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen');
        self::assertResponseIsSuccessful();

        $verweis = $crawler->filter('article h3 a')->first();
        self::assertGreaterThan(0, $verweis->count(), 'Kein Titel-Verweis auf der Karte.');

        $klassen = (string) $verweis->attr('class');
        self::assertStringContainsString(
            'min-h-[44px]',
            $klassen,
            'Der Titel-Verweis hat keine Mindesthöhe mehr — er war einmal 18 px hoch (BF-104).',
        );
        self::assertStringContainsString('flex', $klassen, 'Ohne Blockdarstellung wirkt die Mindesthöhe nicht.');
        self::assertStringContainsString(
            'w-full',
            $klassen,
            'Ohne volle Breite ist ein kurzer Titel ein 36 px schmales Ziel (BF-107).',
        );

        $ueberschrift = $crawler->filter('article h3')->first();
        $hKlassen = (string) $ueberschrift->attr('class');
        self::assertStringContainsString(
            'basis-full',
            $hKlassen,
            'Ohne volle Zeile unter `sm:` schrumpft der Titel neben dem Statusabzeichen auf 37 px (BF-107).',
        );
    }

    /**
     * BF-106: Jedes Element mit Nutzertext trägt eine Umbruchregel.
     *
     * Ein Titel aus 80 Zeichen ohne Leerzeichen — innerhalb der erlaubten 120 —
     * sprengte das Board um 1089 px bei 320 px Breite.
     */
    public function testBF106_NutzertextTraegtEineUmbruchregel(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $langesWort = str_repeat('W', 80);
        $idee = (new BoardIdea())
            ->setTitle($langesWort)
            ->setDescription($langesWort)
            ->setSlug('langes-wort')
            ->setLocale('de');
        $idee->setPublishedAt(new \DateTimeImmutable());
        $idee->setTeamResponse($langesWort);
        $em->persist($idee);
        $em->flush();

        $crawler = $client->request('GET', self::LOCALE . '/community/ideen');
        self::assertResponseIsSuccessful();

        foreach ([['article h3 a', 'Titel'], ['article p', 'Beschreibung']] as [$wahl, $name]) {
            $knoten = $crawler->filter($wahl)->first();
            self::assertGreaterThan(0, $knoten->count(), "Element für {$name} nicht gefunden.");
            self::assertStringContainsString(
                'wrap-anywhere',
                (string) $knoten->attr('class'),
                "{$name} trägt keine Umbruchregel — ein Wort ohne Trennstelle sprengt die Karte (BF-106).",
            );
        }
    }
}
