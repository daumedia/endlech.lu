<?php

namespace App\Comparison;

/**
 * Eine Fußnote unter der Merkmalstabelle: Quelle, Adresse, Prüfdatum.
 *
 * ⚠ Das Datum hängt an der **Quelle**, nicht an der Seite. Ein Datum je Seite
 * wäre einfacher zu pflegen und genau deshalb falsch: Wer eine einzelne Zeile
 * nachprüft, datierte damit stillschweigend alle übrigen mit, die er nicht
 * angesehen hat – die veröffentlichte Aussage wäre frischer, als die Prüfung war.
 *
 * Zulässig sind ausschließlich Primärquellen: Hilfeseiten und Dokumentation des
 * Anbieters selbst. Ein Blogbeitrag über Google Maps ist keine Quelle über
 * Google Maps.
 */
final readonly class ComparisonSource
{
    public function __construct(
        public int $ref,
        public string $labelKey,
        public string $url,
        public \DateTimeImmutable $checkedAt,
    ) {
    }
}
