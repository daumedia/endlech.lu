<?php

use App\Kernel;

// Wartungsschalter. Urspruenglich gegen ENDLECH-5 gebaut: Der SSH-Deploy nach
// Cloudways legte diese Datei vor `git reset --hard` an, weil dort neue
// PHP-Dateien neben dem kompilierten Container des Vorgaengers lagen und jede
// Anfrage in einem 500er endete (gemessen am 29.08.2026: `ApiRateLimitSubscriber`
// mit zwei statt drei Argumenten).
//
// Seit dem Wechsel auf Coolify (2026-09-02) gibt es dieses Fenster nicht mehr —
// ein Container-Tausch laesst den alten Container laufen, bis der neue steht. Die
// Pruefung bleibt trotzdem: Sie ist der einzige Weg, die Seite ohne einen Deploy
// stillzulegen, und kostet ein `file_exists` je Anfrage. Angelegt wird die Datei
// jetzt von Hand:
//
//     docker exec <container> touch var/maintenance
//
// Sie steht weiterhin VOR `vendor/autoload_runtime.php`: Sie darf weder den
// Container noch den Autoloader brauchen. Und unter `var/`, weil das gitignoriert
// ist und ein Container-Neustart sie damit ohnehin verwirft.
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
