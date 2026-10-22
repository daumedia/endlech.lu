<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Nonce für die Inline-Skripte, die `importmap()` in den Seitenkopf schreibt.
 *
 * Seit der Umstellung auf AssetMapper (2026-10-22) trägt jede Seite zwei
 * Inline-Skripte: das Importmap selbst und `import 'app'`. `script-src 'self'`
 * erlaubt keines davon — die Richtlinie meldete auf jeder Seite Verstöße, und
 * scharf geschaltet liefe kein JavaScript.
 *
 * ⚠ **Die Nonce gehört ins Attribut, nie in einen Skripttext.** Turbo leert vor
 * dem Vergleich des Seitenkopfs das `nonce`-Attribut, nicht den Inhalt; ein Text
 * mit der Nonce wechselt je Anfrage, und mit `data-turbo-track` lädt Turbo dann
 * bei jeder Navigation die ganze Seite neu (deshalb der eigene Polyfill-Lader).
 *
 * ⚠ **Eine Nonce je Anfrage, an der Hauptanfrage abgelegt.** Template und
 * `SecurityHeadersSubscriber` müssen denselben Wert sehen; eine Fehlerseite wird
 * in einer Unteranfrage gerendert, die Kopfzeile aber an der Hauptanfrage gesetzt.
 *
 * ⚠ **Kein Hash statt der Nonce.** Der Inhalt des Importmaps wechselt mit jedem
 * Deploy (Dateinamen mit Prüfsumme); ein Hash müsste bei jeder Antwort neu
 * berechnet werden und verriete nichts, was die Nonce nicht auch leistet.
 */
final class CspNonce
{
    public const string ATTRIBUT = '_csp_nonce';

    public function __construct(private readonly RequestStack $requests)
    {
    }

    public function fuerDieseAnfrage(): string
    {
        $anfrage = $this->requests->getMainRequest();

        // Ohne Anfrage (Mail im Worker, Konsolenbefehl) gibt es keine Kopfzeile,
        // die den Wert prüfen könnte — eine frische Nonce richtet dort nichts aus.
        if (null === $anfrage) {
            return self::erzeuge();
        }

        $vorhanden = $anfrage->attributes->get(self::ATTRIBUT);
        if (\is_string($vorhanden)) {
            return $vorhanden;
        }

        $nonce = self::erzeuge();
        $anfrage->attributes->set(self::ATTRIBUT, $nonce);

        return $nonce;
    }

    private static function erzeuge(): string
    {
        return base64_encode(random_bytes(16));
    }
}
