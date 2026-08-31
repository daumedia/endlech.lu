<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;

/**
 * BF-116: Eine Assoziation mit dem Property-Typ `self` braucht ein explizites
 * `targetEntity`.
 *
 * ⚠ **Dieser Lauf prüft den Quelltext, nicht das Verhalten — und das ist Absicht.**
 * Der Fehler tritt **nur auf PHP 8.4** auf: Dort leitet Doctrine das Ziel aus dem
 * Property-Typ ab und übergibt `self` wörtlich, woraus `App\Entity\self` wird. Auf
 * PHP 8.5 (der lokalen Version) greift die Auflösung, `cache:clear --env=prod` ist
 * grün, und **jede verhaltensbasierte Prüfung bliebe hier blind**.
 *
 * Der Deploy vom 2026-08-31 scheiterte daran auf Produktion und musste zurückgerollt
 * werden — die Seite war währenddessen offline. Eine statische Regel ist der einzige
 * Weg, das lokal zu fangen.
 *
 * Angelegt bei der Reparatur von BF-116 am 2026-08-31.
 */
final class MappingSelfTargetTest extends TestCase
{
    private const string ENTITY_DIR = __DIR__.'/../../../src/Entity';

    /**
     * @return list<array{datei: string, art: string, property: string, typ: string}>
     */
    private static function assoziationen(): array
    {
        $gefunden = [];

        foreach (glob(self::ENTITY_DIR.'/*.php') ?: [] as $pfad) {
            $inhalt = file_get_contents($pfad);
            if (false === $inhalt) {
                continue;
            }

            // Assoziations-Attribut (mit oder ohne Klammern), gefolgt von weiteren
            // Attributen und der Property-Deklaration.
            preg_match_all(
                '/#\[ORM\\\\(ManyToOne|OneToOne|OneToMany|ManyToMany)(\((?:[^()]|\([^()]*\))*\))?\]'
                .'((?:\s*#\[(?:[^\[\]]|\[[^\]]*\])*\])*)\s*'
                .'(?:private|protected|public)\s+([^;$]*)\$(\w+)/',
                $inhalt,
                $treffer,
                \PREG_SET_ORDER,
            );

            foreach ($treffer as $t) {
                $gefunden[] = [
                    'datei' => basename($pfad),
                    'art' => $t[1],
                    'argumente' => $t[2] ?? '',
                    'typ' => trim($t[4]),
                    'property' => $t[5],
                ];
            }
        }

        return $gefunden;
    }

    /**
     * Der Scanner muss überhaupt etwas finden — sonst prüft er ins Leere.
     *
     * ⚠ Genau dieser Fall trat beim Schreiben auf: Das erste Muster verlangte Klammern
     * hinter dem Attribut und übersah `#[ORM\ManyToOne]` — also ausgerechnet die
     * Schreibweise, an der BF-116 hing.
     */
    public function testDerScannerFindetAssoziationen(): void
    {
        $alle = self::assoziationen();

        self::assertGreaterThan(10, \count($alle), 'Der Scanner findet zu wenige Assoziationen — vermutlich greift das Muster nicht.');

        $ohneKlammern = array_filter($alle, static fn (array $a): bool => '' === $a['argumente']);
        self::assertNotEmpty($ohneKlammern, 'Der Scanner erfasst keine Attribute ohne Klammern — genau die Schreibweise von BF-116.');
    }

    /**
     * BF-116: `self` als Property-Typ verlangt ein explizites `targetEntity`.
     */
    public function testSelbstbezuegeTragenEinExplizitesZiel(): void
    {
        $verstoesse = [];

        foreach (self::assoziationen() as $a) {
            if (1 !== preg_match('/^\??self\b/', $a['typ'])) {
                continue;
            }
            if (str_contains($a['argumente'], 'targetEntity')) {
                continue;
            }

            $verstoesse[] = sprintf('%s::$%s (%s, Typ %s)', $a['datei'], $a['property'], $a['art'], $a['typ']);
        }

        self::assertSame([], $verstoesse, sprintf(
            "%d Assoziation(en) mit dem Property-Typ „self\" haben kein explizites `targetEntity`:\n  %s\n\n"
            ."Doctrine leitet das Ziel dann aus dem Typ ab, und **PHP 8.4 übergibt „self\" wörtlich** — "
            ."daraus wird `App\\Entity\\self`, und `cache:clear` bricht auf Produktion ab (BF-116). "
            .'Lösung: `targetEntity: self::class`, das zur Übersetzungszeit aufgelöst wird.',
            \count($verstoesse),
            implode("\n  ", $verstoesse),
        ));
    }
}
