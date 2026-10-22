/*
 * Einstieg der Anwendung — `importmap('app')` in base.html.twig lädt dieses Modul.
 *
 * ⚠ Lokale Importe tragen die Endung `.ts`. AssetMapper löst Pfade ohne Endung
 * nicht auf (im Test- und Entwicklungsbetrieb ein Fehler, in Produktion nur eine
 * Warnung und ein fehlendes Modul); die Endung zeigt auf die Quelldatei, die das
 * TypeScript-Bundle beim Ausliefern durch ihre kompilierte Fassung ersetzt.
 */
import './stimulus_bootstrap.ts';
// Nutzungsmessung (Feature 11): Vor-Versand-Prüfung, die das Zählskript über data-before-send ruft.
import './usage/before_send.ts';

// ⚠ Kein CSS-Import hier. Die Stylesheets (Tailwind, Tom Select, GLightbox) stehen als <link>
// in base.html.twig — ein `import '….css'` legte ein `data:`-Modul ins Importmap, an dem eine
// scharfe CSP den ganzen Modulbaum scheitern ließe (siehe SecurityHeadersSubscriber).

// GLightbox – Lightbox für Restaurant-Fotos
import GLightbox from 'glightbox';

document.addEventListener('DOMContentLoaded', () => {
    GLightbox({ selector: '.glightbox' });
});

// PWA: Service Worker registrieren (Offline-Support, installierbar – Issue #83)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // Registrierung fehlgeschlagen – App funktioniert ohne SW weiter.
        });
    });
}
