import { Controller } from '@hotwired/stimulus';

/**
 * Cookie-Consent-Banner (Issue #82).
 *
 * Zeigt das Banner, wenn noch keine Wahl getroffen wurde, speichert die
 * Entscheidung (akzeptiert/abgelehnt) in einem langlebigen Cookie und lässt sich
 * über den Footer-Link "Cookie-Einstellungen" erneut öffnen.
 */
export default class extends Controller {
    static targets = ['banner'];
    static values = {
        cookieName: { type: String, default: 'cookie_consent' },
        lifetime: { type: Number, default: 365 },
    };

    declare readonly bannerTarget: HTMLElement;
    declare cookieNameValue: string;
    declare lifetimeValue: number;

    // Gebundene Referenz, damit der Listener in disconnect() entfernt werden kann.
    private reopenHandler = (event: Event): void => this.reopen(event);

    connect(): void {
        if (!this.#hasConsent()) {
            this.#show();
        }

        const trigger = document.getElementById('cookie-settings-trigger');
        if (trigger) {
            trigger.addEventListener('click', this.reopenHandler);
        }
    }

    disconnect(): void {
        const trigger = document.getElementById('cookie-settings-trigger');
        if (trigger) {
            trigger.removeEventListener('click', this.reopenHandler);
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

    reopen(event: Event): void {
        event.preventDefault();
        this.#show();
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
