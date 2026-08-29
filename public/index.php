<?php

use App\Kernel;

// Wartungsfenster (ENDLECH-5): `deploy.sh` legt diese Datei an, bevor
// `git reset --hard` die neuen Klassen einspielt, und entfernt sie erst nach
// `cache:clear`. Dazwischen liegen neue PHP-Dateien neben dem kompilierten
// Container des Vorgaenger-Releases – ruft der alte Container dort einen
// geaenderten Konstruktor auf, endet jede Anfrage in einem 500er (gemessen am
// 29.08.2026: `ApiRateLimitSubscriber` mit zwei statt drei Argumenten).
//
// Die Pruefung steht bewusst VOR `vendor/autoload_runtime.php`: Sie darf weder
// den Container noch den Autoloader brauchen, weil genau die waehrend des
// Deploys unvollstaendig sein koennen.
if (file_exists(dirname(__DIR__).'/var/maintenance')) {
    http_response_code(503);
    header('Retry-After: 120');
    // Kein Zwischenspeicher darf eine 503 festhalten – weder Varnish auf dem
    // Hosting noch der Browser. Sonst ueberlebt die Wartungsseite den Deploy.
    header('Cache-Control: no-store, private');
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__.'/maintenance.html');

    exit;
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
