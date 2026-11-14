import { Controller } from '@hotwired/stimulus';

/**
 * Hält den Tastaturfokus am Herz, wenn Turbo es per Stream austauscht (Feature 17, AK-48).
 *
 * Der Stream ersetzt das Herz-Formular per `morph`, das Knopf-Element bleibt dasselbe. Trotzdem
 * war der Fokus danach weg — im Browser gemessen 2026-11-14: Turbo 8 setzt den Absendeknopf
 * während des Sendens auf `disabled` (Vorgabe `forms.submitter`), und ein deaktiviertes Element
 * verliert den Fokus. Wer mit Tastatur oder Screenreader merkt, stünde danach am Seitenanfang.
 *
 * Bewusst lokal statt global (`Turbo.config.forms.submitter = "aria-disabled"`): Die globale
 * Umstellung träfe jedes Formular der Seite.
 *
 * Ablauf: Beim Absenden merkt sich der Controller, dass der Fokus zurück soll; wenn der Stream mit
 * dem eigenen Formular als Ziel gerendert ist, bekommt der Knopf ihn zurück.
 */
export default class extends Controller<HTMLFormElement> {
    private fokusZurueck = false;

    connect(): void {
        document.addEventListener('turbo:before-stream-render', this.beimStream);
    }

    disconnect(): void {
        document.removeEventListener('turbo:before-stream-render', this.beimStream);
    }

    vormerken(): void {
        this.fokusZurueck = true;
    }

    private beimStream = (event: Event): void => {
        const stream = event.target;
        if (!this.fokusZurueck || !(stream instanceof Element) || stream.getAttribute('target') !== this.element.id) {
            return;
        }

        const detail = (event as CustomEvent<{ render: (stream: Element) => Promise<void> | void }>).detail;
        const rendern = detail.render;
        detail.render = async (element: Element): Promise<void> => {
            await rendern(element);
            this.fokusZurueck = false;
            document.getElementById(this.element.id)?.querySelector<HTMLElement>('button')?.focus({ preventScroll: true });
        };
    };
}
