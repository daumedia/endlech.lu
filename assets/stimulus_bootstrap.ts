import { startStimulusApp } from '@symfony/stimulus-bundle';
import { AuthenticationController, RegistrationController } from '@web-auth/webauthn-stimulus';

// Registriert die Controller aus controllers.json und aus assets/controllers/.
// Die Liste erzeugt das StimulusBundle beim Ausliefern; Controller mit
// `/* stimulusFetch: 'lazy' */` lädt es erst, wenn ihr data-controller auftaucht.
const app = startStimulusApp();

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
