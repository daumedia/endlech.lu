import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/*
 * Stimulus-Controller für die Bildsortierung.
 * Zwei gleichwertige Wege, beide senden die neue Reihenfolge per POST an
 * denselben Endpunkt (admin_restaurant_image_sort):
 *   1. Drag & Drop (Maus) via SortableJS.
 *   2. Auf/Ab-Knöpfe je Bild (Tastatur/ohne Ziehen) via moveUp/moveDown.
 */
export default class extends Controller {
    static targets = ['list'];
    static values = { url: String, token: String };

    declare readonly listTarget: HTMLElement;
    declare urlValue: string;
    declare tokenValue: string;

    connect() {
        Sortable.create(this.listTarget, {
            handle: '.drag-handle',
            ghostClass: 'opacity-30',
            animation: 150,
            onEnd: () => {
                this.#updateButtons();
                void this.#persist();
            },
        });

        this.#updateButtons();
    }

    moveUp(event: Event) {
        const button = event.currentTarget as HTMLButtonElement;
        const row = button.closest<HTMLElement>('[data-image-id]');
        const previous = row?.previousElementSibling;
        if (!row || !previous) {
            return;
        }
        previous.before(row);
        this.#afterMove(button, row);
    }

    moveDown(event: Event) {
        const button = event.currentTarget as HTMLButtonElement;
        const row = button.closest<HTMLElement>('[data-image-id]');
        const next = row?.nextElementSibling;
        if (!row || !next) {
            return;
        }
        next.after(row);
        this.#afterMove(button, row);
    }

    // Nach jedem Tastatur-Verschieben: Knopf-Zustände aktualisieren, Fokus
    // sinnvoll halten (wandert der ausgelöste Knopf an den Rand und wird
    // deaktiviert, springt der Fokus auf den Gegenknopf) und speichern.
    #afterMove(button: HTMLButtonElement, row: HTMLElement) {
        this.#updateButtons();

        if (button.disabled) {
            const fallback = row.querySelector<HTMLButtonElement>(
                '[data-sort-button]:not([disabled])',
            );
            fallback?.focus();
        } else {
            button.focus();
        }

        void this.#persist();
    }

    // Erstes Bild kann nicht nach oben, letztes nicht nach unten.
    #updateButtons() {
        const rows = Array.from(this.listTarget.querySelectorAll<HTMLElement>('[data-image-id]'));
        rows.forEach((row, index) => {
            const up = row.querySelector<HTMLButtonElement>('[data-sort-button="up"]');
            const down = row.querySelector<HTMLButtonElement>('[data-sort-button="down"]');
            if (up) {
                up.disabled = index === 0;
            }
            if (down) {
                down.disabled = index === rows.length - 1;
            }
        });
    }

    async #persist() {
        const items = this.listTarget.querySelectorAll<HTMLElement>('[data-image-id]');
        const imageIds = Array.from(items).map((el) => Number(el.dataset.imageId));

        // Cover-Badge aktualisieren: nur beim ersten Element anzeigen
        items.forEach((el, index) => {
            const badge = el.querySelector('[data-cover-badge]');
            if (badge) {
                (badge as HTMLElement).style.display = index === 0 ? '' : 'none';
            }
        });

        await fetch(this.urlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _token: this.tokenValue, imageIds }),
        });
    }
}
