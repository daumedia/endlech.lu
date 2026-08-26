import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = {
        url: String,
        createUrl: String,
    };

    declare urlValue: string;
    declare createUrlValue: string;

    private tomSelect!: TomSelect;

    connect(): void {
        const selectElement = this.element as HTMLSelectElement;

        this.tomSelect = new TomSelect(selectElement, {
            plugins: ['remove_button'],
            valueField: 'id',
            labelField: 'name',
            searchField: ['name'],
            create: this.createUrlValue ? this.handleCreate.bind(this) : false,
            load: this.urlValue ? this.handleLoad.bind(this) : undefined,
            render: {
                option_create: (data: { input: string }) => {
                    return `<div class="create">+ <strong>${this.escapeHtml(data.input)}</strong> hinzufügen</div>`;
                },
            },
        });

        // AK-41: Auswahl für Screenreader ansagen.
        // Die Vorschläge selbst trägt Tom Select bereits barrierefrei aus:
        // role="combobox", aria-expanded, aria-controls sowie aria-activedescendant/
        // aria-selected auf den Optionen im Listbox-Dropdown. Was fehlt, ist die
        // Ansage der GETROFFENEN Auswahl. Dafür wird die Chip-Leiste (.ts-control)
        // zu einer höflichen Live-Region: Ein neu hinzugefügter Küchen-Name wird
        // vorgelesen. Der Chip-Text trägt die Aussage – kein neuer Übersetzungs-
        // schlüssel nötig. Wird nach der Initialisierung gesetzt, damit die bereits
        // vorhandenen Chips beim Laden nicht vorgelesen werden.
        this.tomSelect.control.setAttribute('aria-live', 'polite');
        this.tomSelect.control.setAttribute('aria-relevant', 'additions');
    }

    disconnect(): void {
        this.tomSelect?.destroy();
    }

    private handleLoad(query: string, callback: (results: Array<{ id: string; name: string }>) => void): void {
        const url = `${this.urlValue}?q=${encodeURIComponent(query)}`;
        fetch(url)
            .then((response) => response.json())
            .then((data: Array<{ id: number; name: string }>) => {
                callback(data.map((item) => ({
                    id: String(item.id),
                    name: item.name,
                })));
            })
            .catch(() => callback([]));
    }

    private handleCreate(input: string, callback: (item?: { id: string; name: string }) => void): boolean {
        fetch(this.createUrlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: input }),
        })
            .then((response) => response.json())
            .then((data: { id: number; name: string }) => {
                callback({ id: String(data.id), name: data.name });
            })
            .catch(() => callback());

        return true;
    }

    private escapeHtml(text: string): string {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
