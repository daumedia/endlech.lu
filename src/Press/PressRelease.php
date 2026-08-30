<?php

namespace App\Press;

/**
 * Eine Pressemitteilung.
 *
 * ⚠ Das Datum ist ein `DateTimeImmutable` und wird im Template über
 * `format_date` ausgegeben, nicht über eine feste Schreibweise. Sonst stünde in
 * der luxemburgischen Fassung ein deutsches Datum (EC-10).
 *
 * Es gibt bewusst **keine Detailseite** je Meldung: Die Spec verlangt eine Liste.
 * Eigene Adressen wären neue Seiten mit eigenen Titeln, Sprachverweisen und
 * Einträgen im Barrierefreiheits-Prüflauf — ein Nachtrag, den niemand bestellt
 * hat.
 */
final readonly class PressRelease
{
    public function __construct(
        public \DateTimeImmutable $date,
        public string $titleKey,
        public string $bodyKey,
    ) {
    }
}
