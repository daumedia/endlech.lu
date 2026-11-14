import { Controller } from '@hotwired/stimulus';

/**
 * Setzt nach einem Turbo-Stream den Tastaturfokus auf das Element, das der Server markiert hat
 * (Feature 17, AK-14, AK-26): nach dem Entfernen einer Karte die nächste, bei der letzten die
 * vorige, ist keine mehr übrig, den Leertext. Ohne Fokusziel landete der Fokus auf `<body>` —
 * das Element, auf dem er stand, ist mit der Karte verschwunden.
 *
 * Der Server schreibt `data-controller="focus-after-stream"` **nur in die Stream-Antwort**
 * (`saved/_cards.html.twig`). Weil `replace` mit `method="morph"` ein bestehendes Element nur um
 * das Attribut ergänzt, verbindet Stimulus den Controller genau dann — `connect()` ist der Moment
 * nach dem Stream.
 *
 * ⚠ **Nie auf einer gewöhnlich geladenen Seite** — dort nähme er dem Besucher beim Laden den Fokus
 * weg (`SavedCardsTest` prüft, dass keine Seite ihn ausliefert).
 *
 * ⚠ **Danach nimmt er sich selbst wieder vom Element.** Sonst bliebe die Markierung stehen: Fiele
 * das nächste Fokusziel auf dasselbe Element, änderte der Morph kein Attribut, Stimulus verbände
 * nichts neu, und der Fokus fiele auf `<body>`. Ebenso trüge ein Seitenschnappschuss von Turbo die
 * Markierung sonst in einen späteren Seitenaufruf.
 */
export default class extends Controller<HTMLElement> {
    connect(): void {
        const ziel = this.element;

        // Eine Karte ist von sich aus nicht fokussierbar; der Server setzt `tabindex="-1"` mit,
        // das hier ist nur die zweite Schicht. `-1`: erreichbar per Skript, nicht in der Tab-Folge.
        if (!ziel.hasAttribute('tabindex')) {
            ziel.setAttribute('tabindex', '-1');
        }

        // Ohne `preventScroll`: Liegt das Ziel außerhalb des Sichtbereichs, holt der Browser es
        // hinein; steht es schon da, scrollt nichts (`scroll-mt-24` hält es unter der Kopfzeile frei).
        ziel.focus();

        this.#abmelden();
    }

    #abmelden(): void {
        const uebrige = (this.element.getAttribute('data-controller') ?? '')
            .split(/\s+/)
            .filter((name) => name !== '' && name !== this.identifier);

        if (uebrige.length > 0) {
            this.element.setAttribute('data-controller', uebrige.join(' '));
        } else {
            this.element.removeAttribute('data-controller');
        }

        this.element.removeAttribute('data-fokus-nach-aktualisierung');
    }
}
