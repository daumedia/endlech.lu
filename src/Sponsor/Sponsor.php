<?php

declare(strict_types=1);

namespace App\Sponsor;

/**
 * Ein Sponsor von Endlech.lu (Feature 15).
 *
 * ⚠ **Es gibt kein Feld für einen Betrag, eine Laufzeitart oder eine Person** —
 * und das ist die Zusage, nicht ein Versäumnis. Alle zahlen dasselbe Paket,
 * und ob monatlich oder jährlich, verriete den Betrag (AK-14). Das Zitat ist mit
 * dem Firmennamen gezeichnet, nie mit einem Personennamen (AK-44). Wer hier ein
 * Feld ergänzt, ändert, was die Website über Sponsoren sagen kann.
 *
 * ⚠ **Name, Zitat und Kurzbeschreibung stehen nicht im Übersetzungskatalog.** Eigennamen werden
 * nicht übersetzt, und das Zitat bleibt in der Sprache, in der der Sponsor es
 * geschrieben hat (AK-18). Deshalb braucht ein neuer Sponsor keine Änderung an
 * den fünf Katalogen (AK-26).
 *
 * Was ein gültiger Eintrag ist, prüft `SponsorRules` (AK-27).
 */
final readonly class Sponsor
{
    /**
     * @param string              $slug        letzter Teil der Adresse, nur a–z, 0–9 und Bindestrich
     * @param string              $name        Anzeigename, höchstens 80 Zeichen
     * @param string              $logo        Dateiname unter `assets/images/sponsors/`, PNG oder WebP
     * @param list<SponsorPeriod> $periods     zeitlich geordnet, ohne Überlappung
     * @param string|null         $website     `https://…`, wird nur verlinkt, solange der Sponsor aktiv ist
     * @param string|null         $quote       höchstens 300 Zeichen, in der Originalsprache
     * @param string|null         $quoteLocale Sprache des Zitats (ISO 639-1), Pflicht, sobald es ein Zitat gibt
     * @param array<string, string>|null $description Kurzbeschreibung je Sprache (`lb`, `de`, `fr`, `en`, `pt`),
     *                                                je höchstens 500 Zeichen — alle fünf oder keine (AK-49, AK-51)
     * @param array<string, string> $profiles Profiladressen je Plattform, Schlüssel aus `SponsorPlatform`
     *                                        (`instagram`, `facebook`, `linkedin`), AK-52, AK-53
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $logo,
        public array $periods,
        public ?string $website = null,
        public ?string $quote = null,
        public ?string $quoteLocale = null,
        public ?array $description = null,
        public array $profiles = [],
    ) {
    }

    /** Die Kurzbeschreibung in einer Sprache — oder null, wenn keine vereinbart ist (AK-50). */
    public function descriptionIn(string $locale): ?string
    {
        return $this->description[$locale] ?? null;
    }

    /**
     * Die Profile in der Reihenfolge von `SponsorPlatform` (Instagram, Facebook, LinkedIn).
     * Unbekannte Schlüssel fallen hier weg; `SponsorRules` meldet sie.
     *
     * @return list<array{platform: SponsorPlatform, url: string}>
     */
    public function profileLinks(): array
    {
        $links = [];
        foreach (SponsorPlatform::cases() as $platform) {
            if (isset($this->profiles[$platform->value])) {
                $links[] = ['platform' => $platform, 'url' => $this->profiles[$platform->value]];
            }
        }

        return $links;
    }

    /** Logischer Pfad für AssetMapper (`asset()`). */
    public function logoPath(): string
    {
        return SponsorRegistry::LOGO_DIRECTORY.'/'.$this->logo;
    }
}
