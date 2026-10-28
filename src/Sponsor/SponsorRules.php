<?php

declare(strict_types=1);

namespace App\Sponsor;

/**
 * Was ein gültiger Sponsoreneintrag ist (Feature 15, AK-27, AK-28, AK-10).
 *
 * `SponsorRegistryTest` legt jeden Eintrag der Produktionsquelle hier vor und
 * wird rot, sobald eine Angabe fehlt oder widersprüchlich ist — mit Sponsor und
 * Angabe im Text, damit der Fehler ohne Suchen zu beheben ist.
 *
 * ⚠ **Geprüft wird im Prüflauf, nicht zur Laufzeit.** Eine Regel, die beim
 * Rendern würfe, nähme wegen eines Tippfehlers in einem Logo-Namen jede Seite
 * mit — der Footer steht überall.
 */
final class SponsorRules
{
    public const int NAME_MAX = 80;
    public const int QUOTE_MAX = 300;
    public const int DESCRIPTION_MAX = 500;
    public const int LOGO_MAX_BYTES = 25 * 1024;
    public const int FOOTER_MAX_BYTES = 150 * 1024;

    private const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * @param list<Sponsor> $sponsors
     * @param list<string>  $removedSlugs
     * @param string        $logoDirectory Verzeichnis auf der Platte, in dem die Logos liegen
     * @param list<string>  $locales       die Sprachen der Website (`framework.enabled_locales`) — eine
     *                                     Kurzbeschreibung braucht genau diese (AK-51)
     *
     * @return list<string> ein Satz je Verstoß; leer heißt gültig
     */
    public static function violations(array $sponsors, array $removedSlugs, string $logoDirectory, array $locales): array
    {
        $violations = [];
        $seen = [];
        $sizes = [];

        foreach ($sponsors as $sponsor) {
            $who = 'Sponsor `'.$sponsor->slug.'`';

            if (1 !== preg_match(self::SLUG_PATTERN, $sponsor->slug)) {
                $violations[] = $who.': Adresse nur aus a–z, 0–9 und einzelnen Bindestrichen (EC-05).';
            }
            if (isset($seen[$sponsor->slug])) {
                $violations[] = $who.': Adresse doppelt vergeben.';
            }
            $seen[$sponsor->slug] = true;
            if (\in_array($sponsor->slug, $removedSlugs, true)) {
                $violations[] = $who.': steht zugleich unter den entfernten Adressen (AK-43).';
            }

            $name = trim($sponsor->name);
            if ('' === $name) {
                $violations[] = $who.': Name fehlt.';
            } elseif (mb_strlen($name) > self::NAME_MAX) {
                $violations[] = \sprintf('%s: Name %d Zeichen, erlaubt %d.', $who, mb_strlen($name), self::NAME_MAX);
            }

            foreach (self::periodViolations($sponsor->periods) as $problem) {
                $violations[] = $who.': '.$problem;
            }

            if (null !== $sponsor->website) {
                $host = parse_url($sponsor->website, \PHP_URL_HOST);
                if (!str_starts_with($sponsor->website, 'https://') || !\is_string($host) || '' === $host
                    || false === filter_var($sponsor->website, \FILTER_VALIDATE_URL)) {
                    $violations[] = $who.': Website muss eine vollständige https://-Adresse sein.';
                }
            }

            if (null !== $sponsor->quote) {
                $length = mb_strlen(trim($sponsor->quote));
                if (0 === $length) {
                    $violations[] = $who.': Zitat ist leer — dann ganz weglassen (AK-20).';
                } elseif ($length > self::QUOTE_MAX) {
                    $violations[] = \sprintf('%s: Zitat %d Zeichen, erlaubt %d (AK-19).', $who, $length, self::QUOTE_MAX);
                }
                if (null === $sponsor->quoteLocale || 1 !== preg_match('/^[a-z]{2}$/', $sponsor->quoteLocale)) {
                    $violations[] = $who.': Sprache des Zitats fehlt oder ist kein zweibuchstabiger Code wie `fr` (AK-18).';
                }
            } elseif (null !== $sponsor->quoteLocale) {
                $violations[] = $who.': Sprache angegeben, aber kein Zitat.';
            }

            foreach (self::descriptionViolations($sponsor->description, $locales) as $problem) {
                $violations[] = $who.': '.$problem;
            }
            foreach (self::profileViolations($sponsor->profiles) as $problem) {
                $violations[] = $who.': '.$problem;
            }

            $logoProblem = self::logoViolation($sponsor->logo, $logoDirectory);
            if (null !== $logoProblem) {
                $violations[] = $who.': '.$logoProblem;
            } else {
                $sizes[] = (int) filesize($logoDirectory.'/'.$sponsor->logo);
            }
        }

        // AK-10: Im Footer stehen höchstens zwölf Logos — geprüft wird der ungünstigste Fall.
        rsort($sizes);
        $footerBytes = array_sum(\array_slice($sizes, 0, SponsorDirectory::FOOTER_LIMIT));
        if ($footerBytes > self::FOOTER_MAX_BYTES) {
            $violations[] = \sprintf('Die zwölf größten Logos wiegen zusammen %d KB, erlaubt 150 KB (AK-10).', (int) ceil($footerBytes / 1024));
        }

        return $violations;
    }

    /**
     * @param list<SponsorPeriod> $periods
     *
     * @return list<string>
     */
    private static function periodViolations(array $periods): array
    {
        if ([] === $periods) {
            return ['kein Zeitraum — mindestens einer mit Beginn.'];
        }

        $problems = [];
        $previous = null;
        foreach ($periods as $i => $period) {
            $nr = $i + 1;
            if (!self::isDay($period->start)) {
                $problems[] = "Beginn von Zeitraum $nr ist kein Tag im Format JJJJ-MM-TT.";
                continue;
            }
            if (null !== $period->end && !self::isDay($period->end)) {
                $problems[] = "Ende von Zeitraum $nr ist kein Tag im Format JJJJ-MM-TT.";
                continue;
            }
            if (null !== $period->end && $period->end < $period->start) {
                $problems[] = "Ende von Zeitraum $nr liegt vor dem Beginn.";
            }
            if (null !== $previous) {
                if ($period->start < $previous->start) {
                    $problems[] = "Zeitraum $nr steht vor einem früheren — Reihenfolge nach Beginn.";
                } elseif (null === $previous->end || $previous->end >= $period->start) {
                    $problems[] = "Zeitraum $nr überlappt den vorigen (nur der letzte darf ohne Ende sein).";
                }
            }
            $previous = $period;
        }

        return $problems;
    }

    /**
     * AK-51: alle Sprachen der Website oder keine, je 1–500 Zeichen, kein Betrag. Ein Betrag
     * in der Beschreibung verriete, was gezahlt wird — oder machte aus der Seite eine Anzeige
     * (AK-14, VB-01).
     *
     * @param array<string, string>|null $description
     * @param list<string>               $locales
     *
     * @return list<string>
     */
    private static function descriptionViolations(?array $description, array $locales): array
    {
        if (null === $description) {
            return [];
        }

        $problems = [];
        foreach (array_diff($locales, array_keys($description)) as $fehlt) {
            $problems[] = "Kurzbeschreibung fehlt in der Sprache `$fehlt` — alle Sprachen der Website oder keine (AK-51).";
        }
        foreach ($description as $locale => $text) {
            if (!\in_array($locale, $locales, true)) {
                $problems[] = "Kurzbeschreibung in `$locale`, einer Sprache, die die Website nicht führt.";
                continue;
            }
            $length = mb_strlen(trim($text));
            if (0 === $length) {
                $problems[] = "Kurzbeschreibung in `$locale` ist leer.";
            } elseif ($length > self::DESCRIPTION_MAX) {
                $problems[] = \sprintf('Kurzbeschreibung in `%s` hat %d Zeichen, erlaubt %d (AK-51).', $locale, $length, self::DESCRIPTION_MAX);
            }
            if (1 === preg_match('/€|\bEUR\b/u', $text)) {
                $problems[] = "Kurzbeschreibung in `$locale` nennt einen Eurobetrag (AK-51, AK-14).";
            }
        }

        return $problems;
    }

    /**
     * AK-53: nur bekannte Plattformen, nur `https://`, und der Host gehört zur Plattform.
     *
     * @param array<string, string> $profiles
     *
     * @return list<string>
     */
    private static function profileViolations(array $profiles): array
    {
        $problems = [];
        foreach ($profiles as $key => $url) {
            $platform = SponsorPlatform::tryFrom((string) $key);
            if (null === $platform) {
                $problems[] = "Profil `$key` ist keine erlaubte Plattform (Instagram, Facebook, LinkedIn).";
                continue;
            }
            if (!str_starts_with($url, 'https://') || false === filter_var($url, \FILTER_VALIDATE_URL) || !$platform->owns($url)) {
                $problems[] = \sprintf('Profil %s muss eine https://-Adresse auf %s sein (AK-53).', $platform->label(), implode(' oder ', $platform->hosts()));
            }
        }

        return $problems;
    }

    private static function isDay(string $value): bool
    {
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false !== $day && $day->format('Y-m-d') === $value;
    }

    /** AK-28: nur PNG und WebP, erkannt am Inhalt, nicht an der Endung; AK-10: höchstens 25 KB. */
    private static function logoViolation(string $logo, string $directory): ?string
    {
        if ('' === $logo || str_contains($logo, '/') || str_contains($logo, '\\')) {
            return 'Logo muss ein Dateiname ohne Pfad sein.';
        }

        $extension = strtolower(pathinfo($logo, \PATHINFO_EXTENSION));
        if (!\in_array($extension, ['png', 'webp'], true)) {
            return "Logo `$logo` muss PNG oder WebP sein (AK-28).";
        }

        $path = $directory.'/'.$logo;
        if (!is_file($path)) {
            return "Logo `$logo` liegt nicht unter assets/images/sponsors/.";
        }

        $head = (string) file_get_contents($path, false, null, 0, 12);
        $isPng = str_starts_with($head, "\x89PNG\r\n\x1a\n");
        $isWebp = str_starts_with($head, 'RIFF') && 'WEBP' === substr($head, 8, 4);
        if (('png' === $extension && !$isPng) || ('webp' === $extension && !$isWebp)) {
            return "Logo `$logo` ist dem Inhalt nach kein ".strtoupper($extension).' (AK-28).';
        }

        $size = (int) filesize($path);
        if ($size > self::LOGO_MAX_BYTES) {
            return \sprintf('Logo `%s` wiegt %d KB, erlaubt 25 KB (AK-10).', $logo, (int) ceil($size / 1024));
        }

        return null;
    }
}
