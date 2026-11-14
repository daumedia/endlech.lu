<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Feature 17, T03 — AK-65: Eine Notiz steht nie in einem Fehlerbericht.
 *
 * ⚠ sentry/sentry (4.30) hängt den Anfragerumpf bis 10 KB an jedes Ereignis — **unabhängig**
 * von `send_default_pii` (Vorgabe `max_request_body_size: medium`, siehe
 * `vendor/sentry/sentry/src/Integration/RequestIntegration.php`). Ohne die Einstellung ginge
 * bei einem Fehler beim Speichern der Notiztext mit, ebenso Passwortfelder und Board-Texte.
 *
 * Gelesen wird die Datei, nicht der Container: Das Bundle existiert nur in `prod`.
 */
final class SentryRequestBodyTest extends TestCase
{
    public function testAk65ProduktionSendetKeinenAnfragerumpf(): void
    {
        $konfiguration = Yaml::parseFile(\dirname(__DIR__, 3).'/config/packages/sentry.yaml');

        $wert = $konfiguration['when@prod']['sentry']['options']['max_request_body_size'] ?? null;

        self::assertContains(
            $wert,
            ['never', 'none'],
            'Ohne max_request_body_size: never schickt Sentry Formularinhalte (Notizen, Passwörter) mit.',
        );
    }
}
