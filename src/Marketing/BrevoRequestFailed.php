<?php

declare(strict_types=1);

namespace App\Marketing;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

/**
 * Ein Aufruf an Brevos Kontakt-Schnittstelle ist gescheitert.
 *
 * ⚠ **Die Meldung ist immer eine sichere Kurzform** — „HTTP 429" oder
 * „TransportException", nie mehr. Sie ist genau das, was `last_error` im
 * Auftragsbuch aufnimmt (varchar(255)) und was in einer Protokollzeile stehen
 * darf (AK-31).
 *
 * ⚠ **Der Konstruktor ist privat, und das ist der eigentliche Schutz.** Ohne ihn
 * genügt ein `new BrevoRequestFailed($e->getMessage())` an irgendeiner Stelle,
 * damit der Wortlaut der Fremdmeldung wieder im Protokoll steht — genau der
 * Fehler, den BF-45 beim Haltestellen-Dienst hinterlassen hat. Wer eine weitere
 * Fehlerart braucht, legt hier einen benannten Konstruktor an; dabei fällt auf,
 * was in die Meldung wandert. Bei einem öffentlichen Konstruktor fällt es nicht auf.
 */
final class BrevoRequestFailed extends \RuntimeException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Brevo hat geantwortet, aber nicht mit 2xx.
     */
    public static function fromStatus(int $status): self
    {
        return new self('HTTP '.$status, $status);
    }

    /**
     * Die Anfrage kam nicht bis zu einer auswertbaren Antwort (Zeitüberschreitung,
     * DNS, TLS) — oder der HttpClient hat den Status selbst zur Ausnahme gemacht.
     *
     * ⚠ **`$e->getMessage()` wird nicht übernommen**, sondern ausschließlich der
     * Klassenname bzw. der Statuscode. Der ursprüngliche Fehler hängt als
     * `previous` an, damit ein Stacktrace die Ursache noch zeigt — **das ist hier
     * vertretbar, weil der Brevo-Schlüssel als Kopfzeile `api-key` reist und
     * nicht als Query-Parameter**; die Meldung des HttpClient trägt zwar die URL,
     * aber die enthält kein Geheimnis und keine E-Mail-Adresse (anders als bei
     * HAFAS, wo `accessId` in der URL steht — BF-45).
     *
     * ⚠ Trotzdem: **diese Ausnahme nie mit dem Kontextschlüssel `exception`
     * protokollieren.** Monolog rendert dann die gesamte Kette samt Fremdmeldung,
     * und der Vorteil der Kurzform wäre dahin.
     */
    public static function fromThrowable(\Throwable $e): self
    {
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getResponse()->getStatusCode();

            return new self('HTTP '.$status, $status, $e);
        }

        return new self(self::kurzerKlassenname($e::class), 0, $e);
    }

    /**
     * Es gibt keinen Schlüssel — der Aufrufer hätte vorher `isConfigured()`
     * fragen müssen (AK-47).
     */
    public static function notConfigured(): self
    {
        return new self('not configured');
    }

    /**
     * Der Rumpf trägt keine `ext_id` — dann lässt sich der Kontakt bei Brevo
     * nicht über die eigene Kennung adressieren, und eine Adressänderung
     * erzeugte dort einen zweiten Kontakt (EC-02). Lauter Abbruch statt stiller
     * Doppelanlage.
     */
    public static function missingExtId(): self
    {
        return new self('missing ext_id');
    }

    private static function kurzerKlassenname(string $fqcn): string
    {
        $position = strrpos($fqcn, '\\');

        return false === $position ? $fqcn : substr($fqcn, $position + 1);
    }
}
