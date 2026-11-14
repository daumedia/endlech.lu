import { Controller } from '@hotwired/stimulus';

/**
 * Rollt nach dem Laden zu einem Element und fokussiert dessen Knopf (Feature 17, AK-09).
 *
 * Wer als Gast auf ein Herz tippt, meldet sich an und soll danach wieder bei derselben Karte
 * stehen. Ohne JavaScript erledigt das der Anker `#favorit-<id>` der Weiterleitung. Mit Turbo
 * geht er verloren: Turbo folgt der Weiterleitung per `fetch`, und `response.url` trägt keinen
 * Anker — im Browser gemessen stand die Seite oben, das Herz 2000 px tiefer.
 *
 * Der Server setzt die Markierung nur nach dem Einlösen eines Merkwunschs, mit
 * `data-turbo-temporary` (BF-177), damit eine Rückkehr aus dem Zwischenspeicher nicht erneut rollt.
 * Der Fokus geht auf den Knopf im Formular — Tastatur und Screenreader stehen dann am Herz,
 * dessen Name jetzt „… aus Favoriten entfernen“ lautet.
 */
export default class extends Controller {
    static values = { id: String };

    declare readonly idValue: string;

    connect(): void {
        // ⚠ Nicht sofort rollen: Stimulus verbindet während Turbo rendert, und Turbo setzt danach
        // die Scrollposition selbst auf oben — im Browser gemessen: Fokus stand am Herz, die Seite
        // trotzdem oben. Deshalb nach `turbo:load` (kommt nach Turbos Scrollen) und zusätzlich im
        // nächsten Durchlauf der Ereignisschleife für den vollen Seitenaufruf. Zweimal rollen schadet nicht.
        const rollen = (): void => {
            const ziel = document.getElementById(this.idValue);
            if (null === ziel) {
                return;
            }

            ziel.scrollIntoView({ block: 'center' });
            ziel.querySelector<HTMLElement>('button')?.focus({ preventScroll: true });
        };

        document.addEventListener('turbo:load', rollen, { once: true });
        window.setTimeout(rollen, 0);
    }
}
