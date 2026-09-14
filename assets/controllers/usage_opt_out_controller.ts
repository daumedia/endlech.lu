import { Controller } from '@hotwired/stimulus';

/** Derselbe Schlüssel, den der Umami-Tracker vor jedem Versand liest. */
const SCHLUESSEL = 'umami.disabled';

/**
 * Widerspruchsschalter der Nutzungsmessung in /legal (Feature 11, AK-23).
 *
 * ⚠ **Kein Cookie** — der Bannertext „Wir nutzen nur technisch notwendige Cookies" muss wahr
 * bleiben (AK-20). Der Schalter schreibt `umami.disabled` in den Browserspeicher. Genau diesen
 * Schlüssel prüft der Umami-Tracker vor **jedem** Versand selbst (Entwurf, Entscheidung 10) — ein
 * eigener Merker wäre eine zweite Stelle, die auseinanderlaufen kann.
 *
 * ⚠ Der Knopf ist im Markup `hidden` und wird erst hier sichtbar: Ohne JavaScript wird ohnehin nicht
 * gemessen (EC-02), und ein Knopf, der nichts tut, wäre schlechter als keiner. Ist der
 * Browserspeicher gesperrt, erscheint stattdessen der Hinweis „nicht verfügbar".
 *
 * Geleerter Browserspeicher hebt den Widerspruch auf (EC-07) — ohne Cookie und ohne Konto kann der
 * Schalter sich nichts dauerhafter merken. `/legal` sagt das.
 */
export default class extends Controller {
    static targets = ['button', 'state', 'unavailable'];
    static values = {
        onText: String,
        offText: String,
    };

    declare readonly buttonTarget: HTMLButtonElement;
    declare readonly stateTarget: HTMLElement;
    declare readonly unavailableTarget: HTMLElement;
    declare readonly onTextValue: string;
    declare readonly offTextValue: string;

    connect(): void {
        const speicher = this.#speicher();
        if (speicher === null) {
            this.unavailableTarget.hidden = false;

            return;
        }

        this.buttonTarget.hidden = false;
        this.#zeige(speicher);
    }

    toggle(): void {
        const speicher = this.#speicher();
        if (speicher === null) {
            return;
        }

        if (this.#istAus(speicher)) {
            speicher.removeItem(SCHLUESSEL);
        } else {
            speicher.setItem(SCHLUESSEL, '1');
        }

        this.#zeige(speicher);
    }

    #zeige(speicher: Storage): void {
        const aus = this.#istAus(speicher);
        // aria-pressed="true" heißt: Die Messung ist AN. Der Zustand steht zusätzlich als Wort da —
        // Farbe trägt nie allein.
        this.buttonTarget.setAttribute('aria-pressed', aus ? 'false' : 'true');
        this.stateTarget.textContent = aus ? this.offTextValue : this.onTextValue;
    }

    #istAus(speicher: Storage): boolean {
        return speicher.getItem(SCHLUESSEL) !== null;
    }

    #speicher(): Storage | null {
        try {
            const speicher = window.localStorage;
            const probe = 'endlech.speicherprobe';
            speicher.setItem(probe, '1');
            speicher.removeItem(probe);

            return speicher;
        } catch {
            return null;
        }
    }
}
