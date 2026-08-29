import { Controller } from '@hotwired/stimulus';

/**
 * Schließt ein <details>-Dropdown bei Escape oder Klick daneben.
 *
 * Rein zusätzlich: Das Aufklappen selbst erledigt <details> nativ – ohne
 * JavaScript bleibt das Menü also voll bedienbar, es schließt sich dann nur
 * nicht von allein. Deshalb wird hier auch kein aria-expanded gepflegt:
 * <details> meldet seinen Zustand bereits selbst an Screenreader.
 *
 * Die Handler sind gebundene Klassenfelder statt #private-Methoden: Babel kann
 * private Felder in der anonymen Controller-Klasse nicht übersetzen
 * ("A class name is required"), obwohl tsc sie akzeptiert.
 */
export default class extends Controller<HTMLDetailsElement> {
    private readonly onOutsideClick = (event: MouseEvent): void => {
        if (!this.element.contains(event.target as Node)) {
            this.element.open = false;
        }
    };

    private readonly onKeydown = (event: KeyboardEvent): void => {
        if (event.key !== 'Escape' || !this.element.open) {
            return;
        }

        this.element.open = false;
        // Fokus zurück auf den Auslöser, sonst landet er im Nirgendwo.
        this.element.querySelector('summary')?.focus();
    };

    connect(): void {
        document.addEventListener('click', this.onOutsideClick);
        document.addEventListener('keydown', this.onKeydown);
    }

    disconnect(): void {
        document.removeEventListener('click', this.onOutsideClick);
        document.removeEventListener('keydown', this.onKeydown);
    }
}
