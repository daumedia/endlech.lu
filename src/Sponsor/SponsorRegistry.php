<?php

declare(strict_types=1);

namespace App\Sponsor;

/**
 * Die Sponsoren von Endlech.lu — im Quelltext gepflegt wie Presse-Kit und
 * Changelog (Feature 15, Decision Log #5).
 *
 * Einen Sponsor eintragen (Anleitung in `CLAUDE.md`, Abschnitt Feature 15):
 *
 *     new Sponsor(
 *         slug: 'beispiel-sarl',
 *         name: 'Beispiel S.à r.l.',
 *         logo: 'beispiel-sarl.webp',          // Datei unter assets/images/sponsors/
 *         periods: [new SponsorPeriod('2027-03-01')],
 *         website: 'https://beispiel.lu',
 *         quote: 'Warum wir das unterstützen …',
 *         quoteLocale: 'de',
 *     ),
 *
 * ⚠ **Vor dem Eintrag liegt die Vereinbarung vor** (VB-01 in
 * `features/15-sponsoren/spec.md`): Logo-Nutzungsrecht, Zitat im Wortlaut, kein
 * Ausschlussgrund (kein Betrieb, der selbst auf Endlech.lu bewertet wird), und
 * die Zustimmung, dass der Eintrag im öffentlichen Quelltext steht (OF-01).
 *
 * ⚠ **Beenden heißt: Ende eintragen, nicht löschen.** Der Sponsor verschwindet
 * am Tag danach von selbst aus dem Footer und bleibt als ehemalig genannt.
 * **Entfernen** (nur auf Wunsch, binnen 30 Tagen, AK-43) heißt: Eintrag und
 * Logodatei löschen und den Slug in `removedSlugs()` aufnehmen.
 *
 * Ob ein Eintrag gültig ist, prüft `SponsorRegistryTest` — rot, sobald eine
 * Angabe fehlt oder widersprüchlich ist (AK-27).
 */
final readonly class SponsorRegistry implements SponsorSource
{
    /** Logischer Pfad der Logos für AssetMapper; auf der Platte `assets/images/sponsors/`. */
    public const string LOGO_DIRECTORY = 'images/sponsors';

    public function entries(): array
    {
        return [
        ];
    }

    public function removedSlugs(): array
    {
        return [
        ];
    }
}
