import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'button', 'arrow'];

    declare readonly menuTarget: HTMLElement;
    declare readonly buttonTarget: HTMLElement;
    declare readonly arrowTarget: SVGElement;

    toggle(event: Event): void {
        event.stopPropagation();
        const isOpen = !this.menuTarget.classList.contains('hidden');
        if (isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    close(event: Event): void {
        if (!this.element.contains(event.target as Node)) {
            this.closeMenu();
        }
    }

    /**
     * BF-71: Escape schließt das Menü und gibt den Fokus zurück.
     *
     * `close` hängt an `click@window` und ist damit eine Maushandlung. Wer das Menü
     * per Tastatur öffnet, konnte es ohne Maus nicht wieder schließen — bei einem
     * Element mit `aria-haspopup` widerspricht das den ARIA Authoring Practices.
     */
    closeOnEscape(): void {
        if (this.menuTarget.classList.contains('hidden')) {
            return;
        }

        this.closeMenu();
        this.buttonTarget.focus();
    }

    private openMenu(): void {
        this.menuTarget.classList.remove('hidden');
        this.buttonTarget.setAttribute('aria-expanded', 'true');
        this.arrowTarget.classList.add('rotate-180');
    }

    private closeMenu(): void {
        this.menuTarget.classList.add('hidden');
        this.buttonTarget.setAttribute('aria-expanded', 'false');
        this.arrowTarget.classList.remove('rotate-180');
    }
}
