import { Controller } from '@hotwired/stimulus';

/**
 * Löst ein Ereignis der Nutzungsmessung aus (Feature 11).
 *
 * Drei Arten, es zu verwenden:
 *
 * - **beim Erscheinen** — `data-usage-event-on-connect-value="true"`: sendet, sobald das Element
 *   im Dokument steht. Für die Erfolgsmeldung der Wartelisten, die nur bei Erfolg gerendert wird.
 * - **beim Klick / beim Absenden** — `data-action="click->usage-event#track"` bzw. `submit->…`.
 * - **Filterformular** — `data-action="submit->usage-event#filter"`: sammelt die gesetzten Filter.
 *
 * ⚠ **Nie warten, nie die Navigation anhalten** (Entwurf, Entscheidung 3). Umamis eigene
 * Klick-Attribute halten bei Links ohne `target="_blank"` die Navigation an, bis der Zählaufruf
 * fertig ist — bei `tel:` und `mailto:` wartete der Besucher auf die Messung. Deshalb hier ohne
 * `await`; der Tracker sendet mit `keepalive`, der Aufruf überlebt den Seitenwechsel.
 *
 * ⚠ **Ohne Zählskript tut dieser Controller nichts und wirft nichts** (EC-01, EC-06): kein Skript
 * auf der Seite, Werbeblocker, Widerspruch, offline.
 *
 * ⚠ **Nie Werte, die eine Person beschreiben.** Daten kommen ausschließlich aus
 * `data-usage-event-data-value`, das die Vorlage fest setzt — nie aus `href`, Formularfeldern mit
 * Freitext oder dem Seitentext. Die Weiterleitung weist alles andere ohnehin ab.
 */
export default class extends Controller {
    static values = {
        name: String,
        data: { type: Object, default: {} },
        onConnect: { type: Boolean, default: false },
    };

    declare readonly nameValue: string;
    declare readonly dataValue: Record<string, string>;
    declare readonly onConnectValue: boolean;

    connect(): void {
        if (this.onConnectValue) {
            this.#send(this.dataValue);
        }
    }

    track(): void {
        this.#send(this.dataValue);
    }

    /**
     * Filterformular der Restaurantliste (AK-13).
     *
     * Übertragen werden nur die **Namen** angehakter Ja/Nein-Felder. Der Ort (`city`) und die Küchen
     * (`cuisine[]`) gehen nur als Merker `ort` bzw. `kueche` hinaus — nie ihr Wert.
     */
    filter(): void {
        const formular = this.element as HTMLFormElement;
        const eintraege: string[] = [];

        formular.querySelectorAll<HTMLInputElement>('input[type="checkbox"][value="1"]').forEach((feld) => {
            if (feld.checked && /^[a-z_]+$/.test(feld.name)) {
                eintraege.push(feld.name);
            }
        });

        const ort = formular.querySelector<HTMLInputElement>('input[name="city"]');
        if (ort && ort.value.trim() !== '') {
            eintraege.push('ort');
        }

        const kuechen = formular.querySelector<HTMLSelectElement>('select[name="cuisine[]"]');
        if (kuechen && kuechen.selectedOptions.length > 0) {
            eintraege.push('kueche');
        }

        if (eintraege.length === 0) {
            return;
        }

        this.#send({ filter: [...new Set(eintraege)].join(',') });
    }

    #send(daten: Record<string, string>): void {
        try {
            const umami = window.umami;
            if (!umami || typeof umami.track !== 'function' || this.nameValue === '') {
                return;
            }

            const ergebnis = umami.track(this.nameValue, Object.keys(daten).length > 0 ? daten : undefined);
            void Promise.resolve(ergebnis).catch(() => {
                // Die Messung darf nie einen Fehler in die Seite tragen.
            });
        } catch {
            // Siehe oben.
        }
    }
}
