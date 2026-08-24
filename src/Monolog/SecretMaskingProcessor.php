<?php

namespace App\Monolog;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;

/**
 * Maskiert Geheimnisse, die als Query-Parameter in Protokollzeilen landen.
 *
 * Der Anlass ist BF-45 aus der QA zu B10: Der HAFAS-Schlüssel steht als
 * `accessId` in der URL — so sieht die Schnittstelle die Übergabe vor —, und
 * Symfonys eigener `http_client`-Kanal protokolliert jede Anfrage samt vollständiger
 * URL. In `var/log/dev.log` standen dadurch 30 Zeilen mit dem Schlüssel im Klartext.
 *
 * Der Service selbst reicht die URL nicht mehr weiter (dort behoben). Dieser
 * Processor deckt den zweiten Weg ab: Zeilen, die das Framework schreibt und die
 * kein Anwendungscode je in der Hand hat.
 *
 * ⚠️ **Der `http_client`-Kanal ist in `prod` nicht ausgeschlossen.** `monolog.yaml`
 * filtert dort `["!deprecation", "!doctrine"]`; bei jedem Fehler ab WARNING schreibt
 * der `fingers_crossed`-Handler seinen gesamten Puffer nach `php://stderr`. Ein
 * kanalweiser Ausschluss wäre die gröbere Lösung gewesen — er nähme der Fehlersuche
 * die Information, welche Anfrage vorausging.
 */
#[AsMonologProcessor]
final class SecretMaskingProcessor
{
    /**
     * Parameter, deren Wert nie in einem Protokoll stehen soll.
     *
     * Absichtlich eine kurze, ausdrückliche Liste statt einer Heuristik: Was hier
     * fehlt, fällt in der nächsten QA auf; was eine Heuristik zu viel maskiert,
     * fällt niemandem auf und macht Logs unbrauchbar.
     */
    private const PARAMETER = ['accessId', 'token', 'apikey', 'api_key', 'access_token', 'password'];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->maskiere($record->message),
            context: $this->maskiereTief($record->context),
        );
    }

    private function maskiere(string $text): string
    {
        if (!str_contains($text, '=')) {
            return $text;
        }

        return preg_replace_callback(
            '/\b('.implode('|', array_map('preg_quote', self::PARAMETER)).')=([^&"\s]+)/i',
            static fn (array $t): string => $t[1].'=<maskiert>',
            $text,
        ) ?? $text;
    }

    /**
     * @param array<mixed> $daten
     *
     * @return array<mixed>
     */
    private function maskiereTief(array $daten): array
    {
        foreach ($daten as $schluessel => $wert) {
            if (\is_string($wert)) {
                $daten[$schluessel] = $this->maskiere($wert);
            } elseif (\is_array($wert)) {
                $daten[$schluessel] = $this->maskiereTief($wert);
            }
        }

        return $daten;
    }
}
