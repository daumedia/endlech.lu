import { Controller } from '@hotwired/stimulus';

/**
 * Blendet die typspezifischen Formularblöcke passend zum gewählten
 * Organisationstyp ein und aus.
 *
 * Rein zusätzlich: Ohne JavaScript rendert der FormType alle drei Blöcke, und
 * PRE_SUBMIT verwirft serverseitig die Felder der nicht gewählten Typen. Der
 * Controller ändert also nur, was sichtbar ist – nie, was gültig ist.
 *
 * Der Wechsel wird in einer Live-Region angesagt, sonst bekommen
 * Screenreader-Nutzer nicht mit, dass sich das Formular verändert hat.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['block', 'announcer'];
    static values = { announcement: String };

    declare readonly blockTargets: HTMLElement[];
    declare readonly announcerTarget: HTMLElement;
    declare readonly hasAnnouncerTarget: boolean;
    declare announcementValue: string;

    connect(): void {
        this.update(false);
    }

    change(): void {
        this.update(true);
    }

    private update(announce: boolean): void {
        const selected = this.selectedType();

        this.blockTargets.forEach((block) => {
            const matches = block.dataset.type === selected;
            block.hidden = !matches;

            // Felder des nicht gewählten Typs aus der Tab-Reihenfolge nehmen –
            // `hidden` allein genügt bei manchen Kombinationen nicht.
            block.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
                'input, select, textarea',
            ).forEach((field) => {
                field.disabled = !matches;
            });
        });

        if (announce && selected) {
            this.announce(selected);
        }
    }

    private selectedType(): string | null {
        const checked = this.element.querySelector<HTMLInputElement>('input[type="radio"]:checked');

        return checked ? checked.value : null;
    }

    private announce(type: string): void {
        if (!this.hasAnnouncerTarget) {
            return;
        }

        const block = this.blockTargets.find((b) => b.dataset.type === type);
        const label = block?.dataset.label ?? '';

        // Kurz leeren, damit auch eine wiederholte Auswahl neu vorgelesen wird.
        this.announcerTarget.textContent = '';
        window.setTimeout(() => {
            this.announcerTarget.textContent = this.announcementValue.replace('%type%', label);
        }, 50);
    }
}
