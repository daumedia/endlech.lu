import { startStimulusApp } from '@symfony/stimulus-bridge';
import { AuthenticationController, RegistrationController } from '@web-auth/webauthn-stimulus';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
// register any custom, 3rd party controllers here

// Passkeys: Die beiden Controller des WebAuthn-Bundles bringen den
// WebAuthn-Ablauf samt base64url-Kodierung und Fehlerklassen mit.
//
// Bewusst hier und NICHT in controllers.json: Das StimulusBundle löst jeden
// Eintrag dort gegen ein gleichnamiges Composer-Paket auf – das Paket lebt aber
// nur auf npm, der Container-Build bräche mit "Could not find package".
//
// Eigene, kurze Bezeichner statt der langen Vorgabe aus der Bundle-Doku: Die
// Templates schreiben die data-Attribute ohnehin von Hand, und
// `data-passkey-auth-…` liest sich besser als
// `data-web-auth--webauthn-stimulus--authentication-…`.
app.register('passkey-auth', AuthenticationController);
app.register('passkey-register', RegistrationController);
