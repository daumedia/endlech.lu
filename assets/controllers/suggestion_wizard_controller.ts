import { Controller } from '@hotwired/stimulus';

// Markierung für unbeantwortete Pflichtfragen
const MISSING_CLASSES = ['ring-2', 'ring-red-400', 'ring-offset-2', 'p-2', '-m-2'];

export default class extends Controller {
    static targets = ['step', 'indicator', 'prevButton', 'nextButton', 'submitButton', 'error'];
    static values = {
        current: { type: Number, default: 1 },
        total: Number,
        incompleteMessage: String,
    };

    declare currentValue: number;
    declare totalValue: number;
    declare incompleteMessageValue: string;
    declare readonly stepTargets: HTMLElement[];
    declare readonly indicatorTargets: HTMLElement[];
    declare readonly prevButtonTarget: HTMLElement;
    declare readonly nextButtonTarget: HTMLElement;
    declare readonly submitButtonTarget: HTMLElement;
    declare readonly errorTarget: HTMLElement;
    declare readonly hasErrorTarget: boolean;

    connect(): void {
        this.updateView();
    }

    next(): void {
        if (!this.validateStep()) {
            return;
        }

        if (this.currentValue < this.totalValue) {
            this.currentValue++;
            this.updateView();
        }
    }

    prev(): void {
        if (this.currentValue > 1) {
            this.currentValue--;
            this.updateView();
        }
    }

    goTo(event: Event): void {
        const target = event.currentTarget as HTMLElement;
        const step = parseInt(target.dataset.step || '1', 10);
        if (step >= 1 && step <= this.totalValue) {
            this.currentValue = step;
            this.updateView();
        }
    }

    /**
     * Prüft, ob im aktuellen Step alle dreiwertigen Pflichtfragen beantwortet sind.
     * Reine UX-Hilfe – die eigentliche Absicherung ist der NotNull-Constraint im Form-Type.
     */
    private validateStep(): boolean {
        const step = this.stepTargets[this.currentValue - 1];
        if (!step) {
            return true;
        }

        const groups = Array.from(step.querySelectorAll<HTMLElement>('[data-tristate]'));
        const isAnswered = (group: HTMLElement): boolean =>
            group.querySelector('input[type="radio"]:checked') !== null;

        for (const group of groups) {
            const answered = isAnswered(group);
            group.classList[answered ? 'remove' : 'add'](...MISSING_CLASSES);
            group.setAttribute('aria-invalid', answered ? 'false' : 'true');
        }

        const missing = groups.find((group) => !isAnswered(group));

        if (!missing) {
            this.clearErrors();

            return true;
        }

        if (this.hasErrorTarget) {
            this.errorTarget.textContent = this.incompleteMessageValue;
            this.errorTarget.classList.remove('hidden');
        }

        missing.scrollIntoView({ block: 'center', behavior: 'smooth' });
        missing.querySelector<HTMLInputElement>('input[type="radio"]')?.focus({ preventScroll: true });

        return false;
    }

    private clearErrors(): void {
        this.element.querySelectorAll<HTMLElement>('[data-tristate]').forEach((group) => {
            group.classList.remove(...MISSING_CLASSES);
            group.removeAttribute('aria-invalid');
        });

        if (this.hasErrorTarget) {
            this.errorTarget.textContent = '';
            this.errorTarget.classList.add('hidden');
        }
    }

    private updateView(): void {
        this.clearErrors();

        // Steps ein-/ausblenden
        this.stepTargets.forEach((el, index) => {
            el.classList.toggle('hidden', index + 1 !== this.currentValue);
        });

        // Step-Indikatoren aktualisieren
        this.indicatorTargets.forEach((el, index) => {
            const stepNum = index + 1;
            const circle = el.querySelector('[data-circle]') as HTMLElement;
            const label = el.querySelector('[data-label]') as HTMLElement;
            const line = el.querySelector('[data-line]') as HTMLElement;

            if (circle) {
                circle.classList.remove('bg-cyan-600', 'text-white', 'bg-green-500', 'bg-gray-200', 'text-gray-500');
                if (stepNum === this.currentValue) {
                    circle.classList.add('bg-cyan-600', 'text-white');
                } else if (stepNum < this.currentValue) {
                    circle.classList.add('bg-green-500', 'text-white');
                } else {
                    circle.classList.add('bg-gray-200', 'text-gray-500');
                }
            }

            if (label) {
                label.classList.remove('text-cyan-700', 'font-semibold', 'text-green-700', 'text-gray-400');
                if (stepNum === this.currentValue) {
                    label.classList.add('text-cyan-700', 'font-semibold');
                } else if (stepNum < this.currentValue) {
                    label.classList.add('text-green-700');
                } else {
                    label.classList.add('text-gray-400');
                }
            }

            if (line) {
                line.classList.remove('bg-green-500', 'bg-gray-200');
                line.classList.add(stepNum < this.currentValue ? 'bg-green-500' : 'bg-gray-200');
            }
        });

        // Buttons
        this.prevButtonTarget.classList.toggle('hidden', this.currentValue === 1);
        this.nextButtonTarget.classList.toggle('hidden', this.currentValue === this.totalValue);
        this.submitButtonTarget.classList.toggle('hidden', this.currentValue !== this.totalValue);
    }
}
