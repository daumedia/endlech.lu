<?php

/**
 * Importmap der Anwendung (Symfony AssetMapper).
 *
 * - "path" ist ein Pfad im AssetMapper-Bestand (`debug:asset-map` listet alle).
 * - "entrypoint" markiert Module, die `importmap()` im Template direkt lädt.
 * - "version" sind npm-Pakete; `importmap:install` lädt sie nach `assets/vendor/`
 *   (gitignored, im Container-Build und in der CI frisch geholt).
 *
 * ⚠ **Die Versionen stehen ein zweites Mal in `package.json`.** Dort dienen sie
 * nur `tsc` und ESLint als Typquelle, gebaut wird mit Node nichts mehr. Wer hier
 * eine Version hebt (`importmap:require paket@x.y.z`), zieht sie dort mit —
 * sonst prüft `npm run typecheck` gegen eine andere API, als der Browser lädt.
 *
 * ⚠ **`@orchidjs/*` steht ausdrücklich da**, obwohl `tom-select` sie selbst
 * mitbringt: `importmap:require tom-select` scheiterte am 2026-10-22 mit
 * „Unable to find the latest version for package @orchidjs/sifter". Mit fester
 * Version läuft es durch.
 *
 * ⚠ **`es-module-shims` liegt lokal, nicht auf dem CDN.** Fehlt der Eintrag,
 * lädt `importmap()` das Polyfill für Browser ohne Importmap-Unterstützung
 * (Safari < 16.4, also iPhones bis iOS 15) von `ga.jspm.io` — eine fremde
 * Herkunft, die die IP des Besuchers sieht und die CSP verletzt. Der Eintrag
 * selbst erscheint nicht im Importmap; nur das Lade-Skript verweist darauf.
 */
return [
    'app' => [
        'path' => './assets/app.ts',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.23',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select' => [
        'version' => '2.5.2',
    ],
    'tom-select/dist/css/tom-select.css' => [
        'version' => '2.5.2',
        'type' => 'css',
    ],
    'glightbox' => [
        'version' => '3.3.1',
    ],
    'glightbox/dist/css/glightbox.css' => [
        'version' => '3.3.1',
        'type' => 'css',
    ],
    'sortablejs' => [
        'version' => '1.15.7',
    ],
    '@simplewebauthn/browser' => [
        'version' => '13.3.0',
    ],
    '@web-auth/webauthn-stimulus' => [
        'version' => '5.3.5',
    ],
    'es-module-shims' => [
        'version' => '2.8.4',
    ],
];
