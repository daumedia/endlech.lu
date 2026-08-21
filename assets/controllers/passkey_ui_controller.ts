import { Controller } from '@hotwired/stimulus';

/**
 * Sichtbarkeit, Ladezustand und verständliche Fehlermeldungen rund um Passkeys.
 *
 * Den WebAuthn-Ablauf selbst übernehmen die beiden Controller aus
 * `@web-auth/webauthn-stimulus` (registriert in stimulus_bootstrap.ts als
 * `passkey-auth` und `passkey-register`). Die melden ihren Fortschritt über
 * aufsteigende CustomEvents – dieser Controller hört darauf und macht daraus
 * das, was das Fremdpaket nicht liefern kann: übersetzten Text und einen
 * Knopf, der erst erscheint, wenn der Browser überhaupt Passkeys beherrscht.
 *
 * Die Meldungen kommen als Values aus dem Template, weil die Übersetzung dort
 * hingehört und nicht in eine JavaScript-Datei.
 */
export default class extends Controller {
    static targets = ['panel', 'button', 'message'];

    static values = {
        unsupported: String,
        failed: String,
        server: String,
        exists: String,
        config: String,
        busy: String,
    };

    declare readonly panelTarget: HTMLElement;
    declare readonly hasPanelTarget: boolean;
    declare readonly buttonTarget: HTMLButtonElement;
    declare readonly hasButtonTarget: boolean;
    declare readonly messageTarget: HTMLElement;
    declare readonly hasMessageTarget: boolean;
    declare unsupportedValue: string;
    declare failedValue: string;
    declare serverValue: string;
    declare existsValue: string;
    declare configValue: string;
    declare busyValue: string;

    private idleLabel = '';

    connect(): void {
        // Ohne WebAuthn im Browser bleibt der Knopf verborgen: Ein Angebot, das
        // beim Antippen nur eine Fehlermeldung liefert, ist schlechter als
        // keines. Der Passwort-Login steht ohnehin daneben.
        if (this.hasPanelTarget && this.#browserSupportsPasskeys()) {
            this.panelTarget.classList.remove('hidden');
        }

        if (this.hasButtonTarget) {
            this.idleLabel = this.buttonTarget.textContent ?? '';
        }
    }

    // Der Ablauf hat begonnen – ab hier wartet der Browser auf Face ID, Touch ID oder PIN.
    start(): void {
        this.#clearMessage();

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.setAttribute('aria-busy', 'true');

            if (this.busyValue !== '') {
                this.buttonTarget.textContent = this.busyValue;
            }
        }
    }

    unsupported(): void {
        this.#reset();
        this.#showMessage(this.unsupportedValue);
    }

    /**
     * Fehler aus dem Ceremony-Teil (navigator.credentials).
     */
    ceremonyError(event: CustomEvent<{ code?: string; name?: string }>): void {
        this.#reset();

        const code = event.detail?.code;

        // Abbruch durch den Nutzer oder abgelaufenes Zeitfenster. Das ist kein
        // Fehler, sondern eine Entscheidung – dafür gibt es keine Meldung.
        if (code === 'ERROR_CEREMONY_ABORTED') {
            return;
        }

        if (code === 'ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED') {
            this.#showMessage(this.existsValue);

            return;
        }

        // Die Domain passt nicht zur konfigurierten relying party id. Betrifft
        // nie den Nutzer, sondern immer die Einrichtung – deshalb ein eigener
        // Text statt der allgemeinen Fehlermeldung.
        if (code === 'ERROR_INVALID_DOMAIN' || code === 'ERROR_INVALID_RP_ID') {
            this.#showMessage(this.configValue);

            return;
        }

        this.#showMessage(this.failedValue);
    }

    /**
     * Fehler auf dem Weg zum oder vom Server.
     */
    serverError(): void {
        this.#reset();
        this.#showMessage(this.serverValue);
    }

    #browserSupportsPasskeys(): boolean {
        return typeof window.PublicKeyCredential === 'function';
    }

    #reset(): void {
        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = false;
            this.buttonTarget.removeAttribute('aria-busy');
            this.buttonTarget.textContent = this.idleLabel;
        }
    }

    #showMessage(text: string): void {
        if (this.hasMessageTarget && text !== '') {
            // Erst sichtbar machen, dann beschriften: Ein role="alert" meldet
            // nur Änderungen, die in einem bereits dargestellten Bereich
            // passieren. Andersherum verschlucken manche Screenreader die
            // Ansage.
            this.messageTarget.classList.remove('hidden');
            this.messageTarget.textContent = text;
        }
    }

    #clearMessage(): void {
        if (this.hasMessageTarget) {
            this.messageTarget.textContent = '';
            this.messageTarget.classList.add('hidden');
        }
    }
}
