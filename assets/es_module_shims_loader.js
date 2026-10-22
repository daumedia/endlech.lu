/*
 * Lädt `es-module-shims` nur in Browsern ohne Importmap (Safari < 16.4, also iPhones bis iOS 15).
 *
 * Ersetzt den Polyfill-Lader, den `importmap()` sonst selbst als Inline-Skript schreibt
 * (`framework.asset_mapper.importmap_polyfill: false`). Der eingebaute trägt die Nonce der
 * Anfrage in seinem TEXT; Turbo vergleicht den Seitenkopf aber nach Text, leert dabei nur das
 * `nonce`-Attribut — jede Navigation sah wie ein neuer Deploy aus und lud die Seite ganz neu
 * (gemessen 2026-10-22: `turbo:reload` mit `tracked_element_mismatch` bei jedem Klick).
 *
 * Bewusst ein klassisches Skript, kein Modul: Es muss gerade dort laufen, wo Module mit
 * Paketnamen nicht auflösen. Die Adresse des Polyfills kommt aus `data-shim` (mit Prüfsumme).
 */
(function () {
    if (HTMLScriptElement.supports && HTMLScriptElement.supports('importmap')) {
        return;
    }

    var shim = document.createElement('script');
    shim.src = document.currentScript.getAttribute('data-shim');
    shim.async = true;
    document.head.appendChild(shim);
})();
