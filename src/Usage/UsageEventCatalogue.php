<?php

declare(strict_types=1);

namespace App\Usage;

use App\Enum\Language;
use App\Enum\OrderingPlatform;

/**
 * Die einzige Liste der Ereignisse, die die Nutzungsmessung kennt (Feature 11).
 *
 * Aus ihr entscheidet die Weiterleitung `/api/send`, welcher Zählaufruf Umami erreicht. Was hier
 * nicht steht, wird abgewiesen — gleich, was ein Browser schickt.
 *
 * ⚠ **Die Liste ist eine Zusage, keine Bequemlichkeit.** AK-12, AK-13, AK-17 und AK-21 versprechen,
 * dass weder Telefonnummer, E-Mail-Adresse, Ortstext noch Kontokennung in die Messung gelangen. Die
 * Tracker-Optionen helfen dabei nur ehrlichen Browsern; ein selbst gebauter Zählaufruf umgeht sie.
 * Wer hier ein Feld ergänzt, prüft zuerst, ob sein Wert eine Person beschreiben kann.
 *
 * ⚠ Die Filterschlüssel sind die Feldnamen des Filterformulars in
 * `templates/restaurant/index.html.twig`. `UsageEventCatalogueTest` gleicht sie mit der Vorlage ab —
 * ein neuer Filter ohne Eintrag hier wird rot, statt lautlos ungezählt zu bleiben.
 */
final readonly class UsageEventCatalogue
{
    public const string FILTER_APPLIED = 'filter_angewandt';
    public const string CONTACT_USED = 'kontaktweg_genutzt';
    public const string WAITLIST_JOINED = 'warteliste_eingetragen';
    public const string VOTE_GIVEN = 'zustimmung_gegeben';
    public const string PRESS_KIT_DOWNLOADED = 'presse_kit_geladen';
    public const string DATASET_DOWNLOADED = 'datensatz_geladen';

    /** Filter mit Ja/Nein-Wert, wie sie die Restaurantliste als Abfrageparameter liest. */
    public const array FILTER_KEYS = [
        'verified',
        'wheelchair',
        'toilet',
        'dogs',
        'lighting',
        'changing_table',
        'disabled_parking',
        'open',
        'vegan',
        'vegetarian',
        'halal',
    ];

    /**
     * Filter, deren **Wert** nie übertragen wird — nur, dass sie gesetzt waren.
     * `ort` steht für das Freitextfeld `city` (AK-13), `kueche` für `cuisine[]`.
     */
    public const array FILTER_MARKERS = ['ort', 'kueche'];

    public const array CONTACT_KINDS = ['website', 'telefon', 'email', 'instagram', 'facebook', 'tiktok', 'bestellweg'];

    public const array WAITLISTS = ['partner', 'organisation', 'app'];

    public const array DATASET_FORMATS = ['csv', 'json'];

    /** Höchstlänge eines Filterwerts: alle Schlüssel samt Kommas passen hinein, mehr nicht. */
    private const int MAX_FILTER_LENGTH = 300;

    /** @return list<string> */
    public function eventNames(): array
    {
        return [
            self::FILTER_APPLIED,
            self::CONTACT_USED,
            self::WAITLIST_JOINED,
            self::VOTE_GIVEN,
            self::PRESS_KIT_DOWNLOADED,
            self::DATASET_DOWNLOADED,
        ];
    }

    /** @return list<string> alle Einträge, die im Wert von `filter` vorkommen dürfen */
    public function allowedFilterEntries(): array
    {
        return [
            ...self::FILTER_KEYS,
            ...array_map(static fn (Language $l): string => 'lang_'.$l->value, Language::cases()),
            ...self::FILTER_MARKERS,
        ];
    }

    /** @return list<string> Datenfelder, die ein Ereignis überhaupt tragen darf */
    public function allowedFields(string $event): array
    {
        return match ($event) {
            self::FILTER_APPLIED => ['filter'],
            self::CONTACT_USED => ['art', 'plattform'],
            self::WAITLIST_JOINED => ['liste'],
            self::DATASET_DOWNLOADED => ['format'],
            default => [],
        };
    }

    /**
     * Ist dieses Ereignis mit genau diesen Daten erlaubt?
     *
     * @param array<mixed> $data
     */
    public function isAllowed(string $event, array $data): bool
    {
        if (!\in_array($event, $this->eventNames(), true)) {
            return false;
        }

        foreach ($data as $feld => $wert) {
            if (!\is_string($feld) || !\in_array($feld, $this->allowedFields($event), true) || !\is_string($wert)) {
                return false;
            }
        }

        return match ($event) {
            self::FILTER_APPLIED => $this->isValidFilter($data),
            self::CONTACT_USED => $this->isValidContact($data),
            self::WAITLIST_JOINED => \in_array($data['liste'] ?? null, self::WAITLISTS, true),
            self::DATASET_DOWNLOADED => \in_array($data['format'] ?? null, self::DATASET_FORMATS, true),
            default => [] === $data,
        };
    }

    /** @param array<string, string> $data */
    private function isValidFilter(array $data): bool
    {
        $wert = $data['filter'] ?? '';
        if ('' === $wert || \strlen($wert) > self::MAX_FILTER_LENGTH) {
            return false;
        }

        $eintraege = explode(',', $wert);
        if (\count($eintraege) !== \count(array_unique($eintraege))) {
            return false;
        }

        return [] === array_diff($eintraege, $this->allowedFilterEntries());
    }

    /** @param array<string, string> $data */
    private function isValidContact(array $data): bool
    {
        $art = $data['art'] ?? null;
        if (!\in_array($art, self::CONTACT_KINDS, true)) {
            return false;
        }

        if (!\array_key_exists('plattform', $data)) {
            return true;
        }

        // Eine Plattform gibt es nur beim Bestellweg; bei allen anderen Arten wäre sie ein Feld,
        // das nichts bedeutet — und damit ein Kanal für Beliebiges.
        $plattformen = array_map(static fn (OrderingPlatform $p): string => $p->value, OrderingPlatform::cases());

        return 'bestellweg' === $art && \in_array($data['plattform'], $plattformen, true);
    }
}
