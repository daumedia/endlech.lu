import { Controller } from '@hotwired/stimulus';

/*
 * Stimulus-Controller für die nach Wochentag gruppierten Öffnungszeiten-Slots.
 * Erlaubt das Hinzufügen mehrerer Zeitslots pro Tag (z. B. Mittag + Abend)
 * und das Entfernen einzelner Slots. Nutzt eine flache Symfony-CollectionType,
 * deshalb wird ein gemeinsamer, über alle Tage eindeutiger Index geführt.
 */
export default class extends Controller {
    static values = { prototype: String };

    declare prototypeValue: string;

    #index!: number;

    connect(): void {
        this.#index = this.element.querySelectorAll('[data-opening-hours-form-target="slot"]').length;
    }

    addSlot(event: Event): void {
        const button = event.currentTarget as HTMLElement;
        const day = button.dataset.openingHoursFormDayParam;
        if (!day) {
            return;
        }

        const container = this.element.querySelector<HTMLElement>(`[data-day="${day}"]`);
        if (!container) {
            return;
        }

        const html = this.prototypeValue.replace(/__name__/g, String(this.#index));
        this.#index++;

        const wrapper = document.createElement('div');
        wrapper.classList.add('flex', 'items-center', 'gap-2');
        wrapper.setAttribute('data-opening-hours-form-target', 'slot');
        wrapper.innerHTML = html +
            '<button type="button" data-action="opening-hours-form#removeSlot" ' +
            'class="text-red-500 hover:text-red-700 text-sm font-bold px-2 py-1 shrink-0 transition">' +
            '✕</button>';

        // Den versteckten dayOfWeek-Input des neuen Slots auf den Zieltag setzen.
        const dayInput = wrapper.querySelector<HTMLInputElement>('input[type="hidden"]');
        if (dayInput) {
            dayInput.value = day;
        }

        container.appendChild(wrapper);
    }

    removeSlot(event: Event): void {
        const target = event.target as HTMLElement;
        const slot = target.closest('[data-opening-hours-form-target="slot"]');
        if (slot) {
            slot.remove();
        }
    }
}
