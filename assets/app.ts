import './stimulus_bootstrap';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// Tom Select CSS für Autocomplete-Selects
import 'tom-select/dist/css/tom-select.css';

// GLightbox – Lightbox für Restaurant-Fotos
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.css';

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
