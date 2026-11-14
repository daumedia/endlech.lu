<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

/**
 * BF-182 und BF-184 (B04) — zwei Eingaben im Profilformular, die als HTTP 500 herauskamen statt als Meldung am Feld.
 *
 * BF-182: `TextType` kürzt „   " auf „" und reicht ohne `'empty_data' => ''` ein `null` an `User::setName(string)` —
 * der Setter wirft, bevor `NotBlank` greift. Ein Browser schickt das ab (`required` gilt mit Leerzeichen als erfüllt).
 * Am Feld `email` dasselbe (dort hält der Browser das leere Feld ab, ein Skript nicht).
 *
 * BF-184: `ProfileType` prüfte die Adresse im HTML5-Modus und ohne Länge. `../../etc/passwd@example.lu` passierte,
 * `Mime\Address` lehnte sie erst beim Versand ab — nach dem `flush()`: Vormerkung gespeichert, Deckelplatz verbraucht,
 * 500. Eine Adresse über 180 Zeichen lief in `SQLSTATE[22001]` an `pending_email`. Das Profil war der fünfte
 * Adresseingang, den BF-119 („alle vier Wege prüfen strikt") nicht erreicht hatte.
 *
 * ⚠ Geprüft wird wie in `Bf119EmailValidierungTest` **beides**: der Statuscode und der Bestand (keine Vormerkung, keine
 * Mail, kein verbrauchter Deckelplatz). Ein Test, der nur auf 422 sieht, bliebe grün, wenn die Prüfung greift, die
 * Vormerkung aber trotzdem entsteht — genau diese Kombination war der Befund.
 */
final class Bf182Bf184ProfilEingabenTest extends AbstractWebTestCase
{
    private const string ADRESSE = 'bf182-bf184@example.org';
    private const string NAME = 'Bf Profil Eingaben';

    // ─── BF-182 · leerer Name, leere Adresse ────────────────────────────────

    /**
     * @return iterable<string, array{?string}>
     */
    public static function leereNamen(): iterable
    {
        yield 'nur Leerzeichen' => ['   '];
        yield 'leer' => [''];
        yield 'Feld fehlt' => [null];
    }

    #[DataProvider('leereNamen')]
    public function testBf182LeererNameZeigtEineMeldungStattHttp500(?string $name): void
    {
        [$client] = $this->angemeldet();

        $this->sendeProfil($client, ['profile[name]' => $name]);

        self::assertResponseStatusCodeSame(422, 'BF-182: Ein leerer Name bekommt eine Meldung, keinen Serverfehler.');
        self::assertSelectorTextContains('form[name="profile"]', 'Bitte gib deinen Namen ein.');
        self::assertSame(self::NAME, $this->zeile($client)['name'], 'Der gespeicherte Name bleibt unverändert.');
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function leereAdressen(): iterable
    {
        yield 'nur Leerzeichen' => ['   '];
        yield 'leer' => [''];
        yield 'Feld fehlt' => [null];
    }

    #[DataProvider('leereAdressen')]
    public function testBf182LeereAdresseZeigtEineMeldungStattHttp500(?string $adresse): void
    {
        [$client] = $this->angemeldet();

        $this->sendeProfil($client, ['profile[email]' => $adresse]);

        self::assertResponseStatusCodeSame(422, 'BF-182: Eine leere Adresse bekommt eine Meldung, keinen Serverfehler.');
        self::assertSelectorTextContains('form[name="profile"]', 'Bitte gib deine E-Mail-Adresse ein.');
        $zeile = $this->zeile($client);
        self::assertSame(self::ADRESSE, $zeile['email']);
        self::assertNull($zeile['pending_email']);
        self::assertEmailCount(0);
    }

    // ─── BF-184 · RFC-widrige und überlange Adresse ─────────────────────────

    /**
     * Dieselben Adressen wie für die vier anderen Eingänge — `Bf127AngriffsadressenTest` prüft, dass jede davon vom
     * HTML5-Default angenommen und von `Mime\Address` abgelehnt wird, also den Befund überhaupt auslösen kann.
     *
     * @return iterable<string, array{string}>
     */
    public static function rfcWidrigeAdressen(): iterable
    {
        yield from Bf119EmailValidierungTest::rfcWidrigeAdressen();
    }

    #[DataProvider('rfcWidrigeAdressen')]
    public function testBf184RfcWidrigeAdresseWirdAmFeldAbgewiesen(string $neu): void
    {
        [$client] = $this->angemeldet();
        $deckel = $this->freieDeckelplaetze($client);

        $this->sendeProfil($client, ['profile[email]' => $neu]);

        self::assertResponseStatusCodeSame(422, 'BF-184: Meldung am Feld statt Serverfehler: '.$neu);
        self::assertSelectorTextContains('form[name="profile"]', 'Bitte gib eine gültige E-Mail-Adresse ein.');
        $this->assertNichtsVorgemerkt($client, $deckel);
    }

    public function testBf184AdresseUeber180ZeichenWirdAmFeldAbgewiesen(): void
    {
        [$client] = $this->angemeldet();
        $deckel = $this->freieDeckelplaetze($client);
        $neu = self::adresseMitLaenge(181);

        // Die Adresse ist RFC-konform und besteht die strikte Prüfung — nur die Länge hält sie auf. Ohne diese Zusicherung
        // könnte der Test auch dann grün sein, wenn `Length` fehlt.
        self::assertCount(0, Validation::createValidator()->validate($neu, new Email(mode: Email::VALIDATION_MODE_STRICT)));

        $this->sendeProfil($client, ['profile[email]' => $neu]);

        self::assertResponseStatusCodeSame(422, 'BF-184: 181 Zeichen passen nicht in `pending_email` — Meldung statt SQLSTATE[22001].');
        self::assertSelectorTextContains('form[name="profile"]', 'höchstens 180 Zeichen');
        $this->assertNichtsVorgemerkt($client, $deckel);
    }

    public function testBf184AdresseMitGenau180ZeichenWirdVorgemerkt(): void
    {
        [$client] = $this->angemeldet();
        $neu = self::adresseMitLaenge(180);

        $this->sendeProfil($client, ['profile[email]' => $neu]);

        self::assertResponseRedirects(self::LOCALE.'/profile');
        self::assertSame($neu, $this->zeile($client)['pending_email']);
    }

    /**
     * Gegenprobe: Die strikte Prüfung darf keine gewöhnliche Adresse abweisen — ein abgewiesener Adresswechsel fiele
     * niemandem auf. `jean-luc@télécom.lu` wies der HTML5-Default ab; strikt geht es durch.
     *
     * @return iterable<string, array{string}>
     */
    public static function gueltigeAdressen(): iterable
    {
        yield from Bf119EmailValidierungTest::gueltigeAdressen();
    }

    #[DataProvider('gueltigeAdressen')]
    public function testBf184GueltigeAdresseWirdWeiterhinVorgemerkt(string $neu): void
    {
        [$client] = $this->angemeldet();

        $this->sendeProfil($client, ['profile[email]' => $neu]);

        self::assertResponseRedirects(self::LOCALE.'/profile');
        $zeile = $this->zeile($client);
        self::assertSame($neu, $zeile['pending_email']);
        self::assertSame(self::ADRESSE, $zeile['email'], 'Vorgemerkt, nicht übernommen (BF-19).');
        self::assertEmailCount(2);
    }

    // ─── Hilfen ─────────────────────────────────────────────────────────────

    /**
     * Ein eigenes Konto je Test — die Fixture-Konten bleiben unberührt, DAMA rollt die Zeile zurück.
     *
     * @return array{KernelBrowser, User}
     */
    private function angemeldet(): array
    {
        $client = static::createClient();
        // Ein Kernel für den ganzen Test: Der Deckel wird vor und nach dem Absenden aus demselben Speicher gelesen.
        $client->disableReboot();
        $client->getContainer()->get('limiter.email_change')->create(self::ADRESSE)->reset();

        $user = new User();
        $user->setName(self::NAME);
        $user->setEmail(self::ADRESSE);
        $user->setPassword('unbenutzt');
        $user->setRoles([]);
        $user->setIsVerified(true);

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        return [$client, $user];
    }

    /**
     * Schickt das Profilformular wie ein Browser ab; `null` nimmt das Feld aus der Anfrage (wie ein Skript, das es
     * weglässt).
     *
     * @param array<string, ?string> $werte
     */
    private function sendeProfil(KernelBrowser $client, array $werte): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $form = $crawler->filter('form[action$="/profile/edit"]')->form();

        foreach ($werte as $feld => $wert) {
            if (null === $wert) {
                $form->remove($feld);
            } else {
                $form[$feld] = $wert;
            }
        }

        $client->submit($form);
    }

    /**
     * Der Stand in der Datenbank, an der Unit of Work vorbei — die Entity im Speicher trägt nach einem abgewiesenen
     * Absenden die Eingabe, die Zeile nicht.
     *
     * @return array{name: string, email: string, pending_email: ?string}
     */
    private function zeile(KernelBrowser $client): array
    {
        /** @var Connection $verbindung */
        $verbindung = $client->getContainer()->get(EntityManagerInterface::class)->getConnection();
        $zeile = $verbindung->fetchAssociative('SELECT name, email, pending_email FROM `user` WHERE email = ?', [self::ADRESSE]);
        self::assertIsArray($zeile, 'Das Konto muss unter der bestätigten Adresse stehen bleiben.');

        return $zeile;
    }

    private function freieDeckelplaetze(KernelBrowser $client): int
    {
        // Gelesen, nicht verbraucht: `consume(0)` nimmt nichts weg und meldet den Stand (keine Prüfung, siehe
        // `ActionLimiter` — hier wird nur gezählt).
        return $client->getContainer()->get('limiter.email_change')->create(self::ADRESSE)->consume(0)->getRemainingTokens();
    }

    private function assertNichtsVorgemerkt(KernelBrowser $client, int $deckelVorher): void
    {
        $zeile = $this->zeile($client);
        self::assertNull($zeile['pending_email'], 'Eine abgewiesene Adresse darf nicht vorgemerkt bleiben.');
        self::assertSame(self::ADRESSE, $zeile['email']);
        self::assertEmailCount(0);
        self::assertSame($deckelVorher, $this->freieDeckelplaetze($client), 'Eine abgewiesene Eingabe verbraucht keinen Deckelplatz (BF-11).');
    }

    /**
     * Eine RFC-konforme Adresse genau dieser Länge: kurzer Local-Part, lange Domain aus Labels unter 64 Zeichen.
     */
    private static function adresseMitLaenge(int $laenge): string
    {
        $adresse = 'anna@'.str_repeat('a', 60).'.'.str_repeat('b', 60).'.'.str_repeat('c', $laenge - 130).'.lu';
        self::assertSame($laenge, \strlen($adresse));

        return $adresse;
    }
}
