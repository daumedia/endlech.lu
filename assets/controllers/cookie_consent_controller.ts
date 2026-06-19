import { Controller } from '@hotwired/stimulus';

/**
 * Cookie-Consent-Banner (Issue #82).
 *
 * Zeigt das Banner, wenn noch keine Wahl getroffen wurde, speichert die
 * Entscheidung (akzeptiert/abgelehnt) in einem langlebigen Cookie und lässt sich
 * über den Footer-Link "Cookie-Einstellungen" erneut öffnen.
 *
 * Der Footer-Link liegt außerhalb des Banner-Elements und ist daher eine eigene
 * Controller-Instanz: sein Klick ruft `openSettings()` auf, das ein Fenster-Event
 * (`cookie-consent:open`) anstößt. Die Banner-Instanz fängt es über den
 * `@window`-Action-Descriptor ab (`reopen`). So bleibt die Stimulus-Event-Delegation
 * intakt – auch wenn Footer oder Banner einzeln (z. B. per Turbo-Frame) neu geladen
 * werden.
 */
export default class extends Controller {
    static targets = ['banner'];
    static values = {
        cookieName: { type: String, default: 'cookie_consent' },
        lifetime: { type: Number, default: 365 },
    };

    declare readonly bannerTarget: HTMLElement;
    declare readonly hasBannerTarget: boolean;
    declare cookieNameValue: string;
    declare lifetimeValue: number;

    connect(): void {
        if (this.hasBannerTarget && !this.#hasConsent()) {
            this.#show();
        }
    }

    accept(): void {
        this.#setConsent('accepted');
        this.#hide();
    }

    decline(): void {
        this.#setConsent('declined');
        this.#hide();
    }

    // Footer-Instanz: stößt ein Fenster-Event an, das die Banner-Instanz abfängt.
    openSettings(): void {
        this.dispatch('open');
    }

    // Banner-Instanz: reagiert auf das Fenster-Event (cookie-consent:open@window).
    reopen(): void {
        if (this.hasBannerTarget) {
            this.#show();
        }
    }

    #show(): void {
        this.bannerTarget.classList.remove('hidden');
        this.bannerTarget.focus();
    }

    #hide(): void {
        this.bannerTarget.classList.add('hidden');
    }

    #hasConsent(): boolean {
        return this.#readCookie(this.cookieNameValue) !== null;
    }

    #setConsent(value: 'accepted' | 'declined'): void {
        const maxAge = this.lifetimeValue * 24 * 60 * 60;
        const cookie = `${this.cookieNameValue}=${value}; path=/; max-age=${maxAge}; samesite=lax`;
        document.cookie = window.location.protocol === 'https:' ? `${cookie}; secure` : cookie;
    }

    #readCookie(name: string): string | null {
        const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const match = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }
}
