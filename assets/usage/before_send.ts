/**
 * Vor-Versand-Prüfung des Zählskripts (Feature 11).
 *
 * Umami ruft diese Funktion vor **jedem** Zählaufruf auf (`data-before-send` am Skript in
 * base.html.twig). Gibt sie `null` zurück, verlässt nichts den Browser.
 *
 * ⚠ **Global Privacy Control** kennt der Tracker nicht, nur „Do Not Track" — deshalb hier (AK-22).
 *
 * ⚠ **Die Pfadregeln stehen hier ein zweites Mal, und das ist Absicht.** Der Seitenkopf lässt das
 * Skript auf Verwaltung, Profil und Token-Seiten weg. Turbo Drive tauscht beim Navigieren aber nur
 * den Seiteninhalt: Ein einmal geladener Tracker bleibt aktiv und zählt 300 ms nach jedem
 * `pushState` — auch auf dem Weg nach `/de/admin`. Turbo ruft `pushState` schon zu Beginn eines
 * Seitenwechsels, eine Markierung im neuen Seiteninhalt käme also womöglich zu spät. Die Adresse
 * steht dagegen im Zählaufruf selbst. Dieselben Regeln prüft die Weiterleitung auf dem Server
 * (`CollectPayloadNormalizer`) — dort als Grenze, hier, damit gar nichts erst abgeht (AK-06, AK-07).
 *
 * Eigene Listen (Feature 17, AK-67) werden **gekürzt, nicht verworfen**: Der Aufruf geht ab, aber
 * unter `/{xx}/gespeichert/listen` ohne Nummer und ohne Seitentitel (er kann den Listennamen
 * tragen, AK-64). `Feature17UsageEventsTest` liest das Muster `LISTENSEITE` aus dieser Datei und
 * prüft es gegen dieselben Fälle wie den Server — die Konstante deshalb einzeilig lassen.
 */

type Nutzlast = { url?: string } & Record<string, unknown>;

const AUSGENOMMENE_PFADE = /^\/[a-z]{2}\/(admin|profile)(\/|$)/;
const TOKEN = /[a-f0-9]{64}/i;
const LISTENSEITE = /^\/([a-z]{2})\/gespeichert\/listen\/./;

export function vorVersand(_typ: string, nutzlast: Nutzlast | null | undefined): Nutzlast | null {
    if (!nutzlast) {
        return null;
    }

    if ((navigator as Navigator & { globalPrivacyControl?: boolean }).globalPrivacyControl === true) {
        return null;
    }

    const adresse = adresseAus(nutzlast.url);
    if (null === adresse || AUSGENOMMENE_PFADE.test(adresse.pathname) || TOKEN.test(adresse.pathname)) {
        return null;
    }

    const liste = LISTENSEITE.exec(adresse.pathname);
    if (null === liste) {
        return nutzlast;
    }

    adresse.pathname = `/${liste[1]}/gespeichert/listen`;
    adresse.search = '';
    adresse.hash = '';

    const gekuerzt: Nutzlast = { ...nutzlast, url: adresse.toString() };
    delete gekuerzt.title;

    return gekuerzt;
}

function adresseAus(adresse: string | undefined): URL | null {
    try {
        return new URL(adresse ?? '', window.location.href);
    } catch {
        return null;
    }
}

declare global {
    interface Window {
        endlechNutzungVorVersand?: typeof vorVersand;
        umami?: { track: (name: string, data?: Record<string, string>) => unknown };
    }
}

window.endlechNutzungVorVersand = vorVersand;
