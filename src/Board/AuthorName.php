<?php

declare(strict_types=1);

namespace App\Board;

use App\Entity\BoardIdea;

/**
 * Leitet den öffentlichen Anzeigenamen einer Idee aus dem Konto ihres
 * Verfassers ab (AK-51).
 *
 * ⚠ **Bewusst kein Feld an der Entität.** Ein beim Einreichen eingefrorener
 * Name überlebte die Kontolöschung und wäre genau der Weg zurück zur Person,
 * den AK-68 ausschließt. Wird der Fremdschlüssel auf `NULL` gesetzt, liefert
 * diese Klasse `null` — und das Template setzt den übersetzten Platzhalter.
 *
 * ⚠ **`User::$name` ist ein einziges Freitextfeld**, kein getrennter Vor- und
 * Nachname. Die Regeln unten sind deshalb kein Randfall, sondern der Normalfall:
 * Was ein Nutzer dort einträgt, ist nicht vorhersagbar.
 *
 * | Eingetragen | Angezeigt |
 * |---|---|
 * | `Anna Katharina Berg` | `Anna B.` |
 * | `Anna Berg` | `Anna B.` |
 * | `Anna` | `Anna` |
 * | leer, nur Leerzeichen, kein Konto | `null` (EC-01) |
 * | ein Wort mit 60 Zeichen | auf 30 Zeichen gekürzt (EC-02) |
 */
final readonly class AuthorName
{
    /** Ab hier wird ein einzelnes Wort gekürzt — sonst bricht die Zeile aus der Karte. */
    public const int MAX = 30;

    public function forIdea(BoardIdea $idea): ?string
    {
        return $this->forName($idea->getSubmittedBy()?->getName());
    }

    /**
     * `null` bedeutet „Beitrag ohne Namen" und wird im Template übersetzt —
     * hier entsteht bewusst kein deutscher Text, sonst stünde er in allen vier
     * Sprachfassungen gleich.
     */
    public function forName(?string $name): ?string
    {
        $name = trim((string) $name);

        if ('' === $name) {
            return null;
        }

        $teile = preg_split('/\s+/u', $name) ?: [];
        $teile = array_values(array_filter($teile, static fn (string $t): bool => '' !== $t));

        if ([] === $teile) {
            return null;
        }

        $vorname = mb_substr($teile[0], 0, self::MAX);

        if (1 === \count($teile)) {
            return $vorname;
        }

        $letztes = $teile[\count($teile) - 1];
        $initial = mb_strtoupper(mb_substr($letztes, 0, 1));

        // Ein letztes „Wort", das nur aus Satzzeichen besteht, ergibt keine
        // brauchbare Initiale — dann bleibt es beim Vornamen allein.
        if ('' === $initial || 1 !== preg_match('/^\p{L}$/u', $initial)) {
            return $vorname;
        }

        return $vorname . ' ' . $initial . '.';
    }
}
