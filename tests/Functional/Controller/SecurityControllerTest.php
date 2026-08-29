<?php

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SecurityControllerTest extends AbstractWebTestCase
{
    /**
     * Die Login-Seite trägt seit den Passkeys zwei Formulare. Deshalb hier
     * durchgehend formWithField() statt filter('form') – sonst greift der Test
     * das Passkey-Formular, das weder _username noch _password kennt.
     */
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('input[name="_password"]')->count());
    }

    public function testLoginPageOffersPasskey(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        self::assertResponseIsSuccessful();
        // Das Formular, das die Assertion aufnimmt, zeigt auf denselben
        // check_path wie der Passwort-Login – dort entscheidet der
        // PasskeyAuthenticator anhand des Feldes.
        self::assertGreaterThan(0, $crawler->filter('input[name="_assertion"]')->count());
        self::assertGreaterThan(0, $crawler->filter('[data-controller="passkey-auth"]')->count());
    }

    public function testLoggedInUserIsRedirected(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/login');

        self::assertResponseRedirects();
    }

    public function testValidCredentialsAuthenticate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        $client->submit($this->formWithField($crawler, '_password', [
            '_username' => 'user@endlech.lu',
            '_password' => 'user123',
        ]));

        self::assertResponseRedirects(self::LOCALE.'/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testInvalidCredentialsAreRejected(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/login');

        $client->submit($this->formWithField($crawler, '_password', [
            '_username' => 'user@endlech.lu',
            '_password' => 'falsches-passwort',
        ]));

        // Zurück zur Login-Seite (Authentifizierung fehlgeschlagen).
        self::assertResponseRedirects(self::LOCALE.'/login');
    }

    /**
     * AK-05 · Ob eine Adresse existiert, darf die Meldung nicht verraten.
     */
    public function testAk05MeldungVerraetNichtObDasKontoExistiert(): void
    {
        $client = static::createClient();

        $meldungen = [];
        foreach ([['user@endlech.lu', 'falschesPasswort'], ['gibtesnicht@endlech.lu', 'falschesPasswort']] as [$mail, $pass]) {
            $crawler = $client->request('GET', self::LOCALE.'/login');
            $client->submit($this->formWithField($crawler, '_username', [
                '_username' => $mail,
                '_password' => $pass,
            ]));
            $crawler = $client->followRedirect();
            $meldungen[] = trim($crawler->filter('[role="alert"]')->first()->text(''));
        }

        self::assertNotSame('', $meldungen[0], 'Es muss überhaupt eine Meldung erscheinen.');
        self::assertSame(
            $meldungen[0],
            $meldungen[1],
            'Falsches Passwort und unbekannte Adresse müssen ununterscheidbar sein.',
        );
    }

    /**
     * AK-10 · Die Rollenschranke greift serverseitig, nicht nur in der Navigation.
     */
    public function testAk10RolleUserErreichtDenAdminbereichNicht(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/admin');

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * EC-04 · Eine Passwortänderung entwertet fremde Sitzungen.
     *
     * Die Rückerfassung nahm das Gegenteil an: `ProfileController::changePassword()`
     * ruft weder eine Session-Invalidierung noch einen Wechsel des
     * remember_me-Geheimnisses auf. Symfony erledigt es trotzdem – der
     * Sicherheitskontext vergleicht bei jedem Request den serialisierten Nutzer aus
     * der Sitzung mit dem frisch geladenen; ein geänderter Passwort-Hash entwertet
     * den Token.
     *
     * Der Test hält diese Framework-Eigenschaft fest, weil sie im Projektcode
     * nirgends sichtbar ist und bei einem Symfony-Update stillschweigend
     * wegfallen könnte.
     */
    public function testEc04PasswortaenderungEntwertetFremdeSitzungen(): void
    {
        $client = static::createClient();

        // Sitzung B – wird später zur "fremden" Sitzung.
        $this->loginAs($client, 'user@endlech.lu');
        $client->request('GET', self::LOCALE.'/profile');
        self::assertResponseIsSuccessful();
        $cookiesSitzungB = $client->getCookieJar()->all();

        // Sitzung A – frische Anmeldung desselben Kontos, hier wird geändert.
        $client->getCookieJar()->clear();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($this->formByAction($crawler, '/profile/password', [
            'change_password[currentPassword]' => 'user123',
            'change_password[newPassword][first]' => 'einNeuesPasswort',
            'change_password[newPassword][second]' => 'einNeuesPasswort',
        ]));

        // Zurück auf die Cookies von Sitzung B.
        $client->getCookieJar()->clear();
        foreach ($cookiesSitzungB as $cookie) {
            $client->getCookieJar()->set($cookie);
        }

        $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseRedirects(
            'http://localhost'.self::LOCALE.'/login',
            null,
            'Eine fremde Sitzung darf eine Passwortänderung nicht überleben.',
        );
    }

    /**
     * AK-11 · Nach wiederholten Fehlversuchen muss die Anmeldung sperren.
     *
     * Behoben am 2026-08-24: `login_throttling` mit fünf Versuchen je 15 Minuten.
     * Manuell belegt – acht Fehlversuche gegen admin@endlech.lu, ab dem sechsten
     * die Meldung „Zu viele fehlgeschlagene Anmeldeversuche"; das richtige Passwort
     * wurde während der Sperre ebenfalls abgewiesen, ein anderes Konto nicht.
     *
     * Bleibt übersprungen, und zwar dauerhaft: `when@test` setzt max_attempts
     * bewusst auf 10000, weil der Limiter-Speicher zwischen Tests nicht
     * zurückgesetzt wird und sich Fehlversuche sonst über die Suite summieren.
     * Ein Test, der die Sperre erreichen will, müsste diese Vorsichtsmaßnahme
     * aushebeln und würde andere Tests unberechenbar machen.
     */
    public function testAk11WiederholteFehlversucheWerdenGesperrt(): void
    {
        self::markTestSkipped('In der Testumgebung ist max_attempts bewusst auf 10000 gesetzt – siehe Kommentar.');

        $client = static::createClient();

        for ($i = 1; $i <= 6; ++$i) {
            $crawler = $client->request('GET', self::LOCALE.'/login');
            $client->submit($this->formWithField($crawler, '_username', [
                '_username' => 'user@endlech.lu',
                '_password' => 'falsch'.$i,
            ]));
        }

        $crawler = $client->followRedirect();
        self::assertStringContainsString(
            'zu viele',
            strtolower($crawler->filter('[role="alert"]')->text('')),
            'Ab dem sechsten Versuch muss eine Sperre greifen.',
        );
    }

    /**
     * AK-12 · Das Abmelden verlangt seit der Reparatur einen POST mit CSRF-Token.
     * Ein GET von einer fremden Seite darf niemanden abmelden.
     *
     * Die Antwort ist seit BF-17 eine Weiterleitung statt einer 403 — für den
     * Aufrufer war die Fehlerseite eine Meldung ohne Fehler. Am Schutz ändert das
     * nichts, und genau das prüft die zweite Hälfte: Der Nutzer bleibt angemeldet.
     */
    public function testAk12AbmeldenPerGetWirdAbgewiesen(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE.'/logout', [], [], [
            'HTTP_REFERER' => 'https://boese.example/falle.html',
        ]);

        self::assertResponseRedirects();

        // Und der Nutzer ist danach immer noch angemeldet.
        $client->request('GET', self::LOCALE.'/profile');
        self::assertResponseIsSuccessful('Der GET-Aufruf hat den Nutzer abgemeldet.');
    }

    /**
     * BF-17 · Ein Gast, der die Abmeldeadresse aufruft, landet auf der Startseite.
     */
    public function testBf17GastWirdZurStartseiteGeschickt(): void
    {
        $client = static::createClient();

        $client->request('GET', self::LOCALE.'/logout');

        self::assertResponseRedirects(self::LOCALE.'/');
    }

    /**
     * AK-08 · Der reguläre Weg über das Formular in der Kopfzeile funktioniert.
     */
    public function testAk08AbmeldenUeberDasFormularFunktioniert(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/');
        $client->submit($crawler->filter('form[action$="/logout"]')->form());

        self::assertResponseRedirects();

        $client->request('GET', self::LOCALE.'/profile');
        self::assertResponseRedirects();
    }

    /**
     * ENDLECH-6 · Ein Submit aus dem Passkey-Formular endet nie in einem 400.
     *
     * Das Passkey-Formular führt kein `_username`. Solange der
     * PasskeyAuthenticator auf einen GEFÜLLTEN `_assertion`-Wert prüfte, fiel
     * ein Submit mit leerer Assertion an den form_login-Authenticator durch –
     * und der wirft dort eine BadRequestHttpException. Statt der Meldung
     * „Passkey-Anmeldung fehlgeschlagen" sah der Nutzer eine nackte
     * Fehlerseite, und Sentry bekam ein Issue je Versuch.
     */
    #[DataProvider('unbrauchbareAssertionen')]
    public function testEndlech6PasskeySubmitOhneBrauchbareAssertionErzeugtKeinen400(string $assertion): void
    {
        $client = static::createClient();
        $client->request('POST', self::LOCALE.'/login', ['_assertion' => $assertion]);

        self::assertResponseRedirects();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unbrauchbareAssertionen(): iterable
    {
        yield 'leer (Ceremony ohne Ergebnis)' => [''];
        yield 'kein JSON' => ['muell'];
        yield 'leeres Objekt' => ['{}'];
        yield 'JSON ohne die nötigen Felder' => ['{"id":"x"}'];
    }
}
