<?php

namespace App\Tests\Functional\Controller;

use App\Entity\AppWaitlistEntry;
use App\Enum\AppPlatform;
use App\Enum\WaitlistStatus;
use App\Repository\AppWaitlistEntryRepository;
use App\Tests\AbstractWebTestCase;
use App\Waitlist\WaitlistConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Test\Constraint as MailerConstraint;

/**
 * Warteliste für die mobile App (Feature 08).
 *
 * Geprüft werden die Wege, die sich nicht am Code ablesen lassen: der
 * Dublettenzweig, die beiden Mails und ihre Zweige, die Antwortcodes und der
 * Widerruf.
 */
final class AppWaitlistControllerTest extends AbstractWebTestCase
{
    private const PFAD = self::LOCALE.'/app';

    public function testSeiteIstOeffentlichUndZeigtBeideePlattformen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PFAD);

        self::assertResponseIsSuccessful();                                  // AK-01
        self::assertCount(2, $crawler->filter('input[name="app_waitlist[platform]"]'));
        self::assertCount(0, $crawler->filter('input[name="app_waitlist[platform]"][checked]')); // AK-04
    }

    public function testSprachfreieAdresseLeitetWeiter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/app');

        self::assertResponseRedirects();                                     // AK-02
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);            // 302, nicht 301
    }

    public function testLeeresFormularLiefert422UndLegtNichtsAn(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PFAD);
        $client->submit($this->formWithField($crawler, 'app_waitlist[email]'));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY); // AK-07
        self::assertSame(0, $this->repository($client)->count([]));
        self::assertEmailCount(0);
    }

    public function testUngueltigeAdresseLiefert422(): void
    {
        $client = static::createClient();
        $this->absenden($client, 'keine-adresse', 'ios');

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY); // AK-08
        self::assertSame(0, $this->repository($client)->count([]));
    }

    public function testGueltigeVormerkungLegtEintragAnUndVerschicktEineMail(): void
    {
        $client = static::createClient();
        $this->absenden($client, 'Neu.Interessent@Example.LU', 'ios');

        self::assertResponseRedirects(self::PFAD);                           // AK-11 (ohne JavaScript)
        $entry = $this->repository($client)->findOneByEmail('neu.interessent@example.lu');

        self::assertNotNull($entry);                                         // AK-09
        self::assertSame('neu.interessent@example.lu', $entry->getEmail());  // normalisiert
        self::assertSame(AppPlatform::IOS, $entry->getPlatform());
        self::assertSame(WaitlistStatus::PENDING, $entry->getStatus());
        self::assertNull($entry->getMarketingConsentAt());                   // AK-52: ohne Häkchen keine Werbe-Einwilligung
        self::assertNotNull($entry->getConfirmationToken());

        self::assertEmailCount(1);                                           // AK-18
        $mail = self::getMailerMessage();
        self::assertStringContainsString('/app/confirmation/', $mail->getHtmlBody());
        self::assertStringNotContainsString('testflight.apple.com', $mail->getHtmlBody()); // AK-19
    }

    public function testHoneypotAntwortetWieDerErfolgSpeichertAberNichts(): void
    {
        $client = static::createClient();
        $this->absenden($client, 'bot@example.lu', 'ios', honeypot: 'https://spam.example');

        self::assertResponseRedirects(self::PFAD);                           // AK-13: identische Antwort
        self::assertSame(0, $this->repository($client)->count([]));
        self::assertEmailCount(0);
    }

    public function testZweiteEintragungLegtKeinenZweitenEintragAn(): void
    {
        $client = static::createClient();
        $this->bestaetigterEintrag($client, 'doppelt@example.lu');

        $this->absenden($client, 'doppelt@example.lu', 'android');

        self::assertResponseRedirects(self::PFAD);                           // AK-15: gleiche Antwort
        self::assertSame(1, $this->repository($client)->count([]));          // AK-16: kein zweiter Eintrag
        self::assertSame(
            AppPlatform::IOS,
            $this->repository($client)->findOneByEmail('doppelt@example.lu')?->getPlatform(),
        );                                                                    // Plattform unverändert
        self::assertEmailCount(0);                                            // keine weitere Mail
    }

    public function testAbgelaufenerVorgangBekommtEineNeueMail(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'abgelaufen@example.lu', AppPlatform::IOS);
        $this->altern($client, $entry, '-8 days');
        $alterToken = $entry->getConfirmationToken();

        $this->absenden($client, 'abgelaufen@example.lu', 'ios');

        self::assertSame(1, $this->repository($client)->count([]));           // AK-17: kein zweiter Eintrag
        self::assertEmailCount(1);                                            // aber eine neue Mail
        self::assertNotSame(
            $alterToken,
            $this->repository($client)->findOneByEmail('abgelaufen@example.lu')?->getConfirmationToken(),
        );                                                                     // mit neuem Token
    }

    public function testBestaetigungSetztStatusUndVerschicktDieZweiteMail(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'bestaetigt@example.lu', AppPlatform::IOS);

        $client->request('GET', self::LOCALE.'/app/confirmation/'.$entry->getConfirmationToken());

        self::assertResponseIsSuccessful();
        $frisch = $this->repository($client)->findOneByEmail('bestaetigt@example.lu');
        self::assertSame(WaitlistStatus::CONFIRMED, $frisch?->getStatus());   // AK-21
        self::assertNotNull($frisch?->getSelfConfirmedAt());
        self::assertNotNull($frisch?->getBetaLinkSentAt());
        self::assertEmailCount(1);                                            // die zweite Mail
    }

    public function testZweiterKlickMeldetBereitsBestaetigtUndSchicktKeineMail(): void
    {
        $client = static::createClient();
        $entry = $this->bestaetigterEintrag($client, 'zweimal@example.lu');

        $client->request('GET', self::LOCALE.'/app/confirmation/'.$entry->getConfirmationToken());

        self::assertResponseIsSuccessful();                                   // AK-25
        self::assertEmailCount(0);
    }

    public function testUnbekannterTokenLiefert404(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/app/confirmation/'.str_repeat('a', 64));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);         // AK-26
    }

    public function testFalschesTokenformatFindetKeineRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/app/confirmation/zu-kurz');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);         // AK-27
    }

    public function testAbgelaufenerLinkLiefert410UndVerweistZurueck(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'alt@example.lu', AppPlatform::IOS);
        $this->altern($client, $entry, '-8 days');

        $crawler = $client->request('GET', self::LOCALE.'/app/confirmation/'.$entry->getConfirmationToken());

        self::assertResponseStatusCodeSame(Response::HTTP_GONE);              // AK-28: 410, nicht 404
        self::assertGreaterThan(
            0,
            $crawler->filter('a[href="'.self::PFAD.'"]')->count(),
            'AK-28: Ohne Rückverweis auf /app ist ein abgelaufener Link eine Sackgasse.',
        );
    }

    public function testAbmeldelinkLoeschtDenEintrag(): void
    {
        $client = static::createClient();
        $entry = $this->bestaetigterEintrag($client, 'weg@example.lu');
        $token = $entry->getConfirmationToken();

        $client->request('GET', self::LOCALE.'/app/abmelden/'.$token);

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->repository($client)->count([]));           // AK-31: gelöscht, nicht markiert

        $client->request('GET', self::LOCALE.'/app/abmelden/'.$token);
        self::assertResponseIsSuccessful();                                   // AK-33: zweiter Klick ist kein Fehler
    }

    public function testNachDerAbmeldungIstDieAdresseWiederFrei(): void
    {
        $client = static::createClient();
        $entry = $this->bestaetigterEintrag($client, 'zurueck@example.lu');
        $client->request('GET', self::LOCALE.'/app/abmelden/'.$entry->getConfirmationToken());

        $this->absenden($client, 'zurueck@example.lu', 'android');

        self::assertSame(1, $this->repository($client)->count([]));           // AK-34
    }

    public function testAndroidBekommtKeinenBetaLink(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'android@example.lu', AppPlatform::ANDROID);

        $client->request('GET', self::LOCALE.'/app/confirmation/'.$entry->getConfirmationToken());

        self::assertEmailCount(1);
        self::assertStringNotContainsString(
            'testflight.apple.com',
            self::getMailerMessage()->getHtmlBody(),
        );                                                                     // AK-23
    }

    public function testJedeMailTraegtEinenAbmeldelink(): void
    {
        $client = static::createClient();
        $this->absenden($client, 'rueckweg@example.lu', 'ios');

        self::assertStringContainsString('/app/abmelden/', self::getMailerMessage()->getHtmlBody()); // AK-30
    }

    /**
     * OF-04 und AK-22/AK-23/AK-24 an der Vorlage selbst.
     *
     * ⚠ **Nicht über den HTTP-Weg.** Dort ist `app.testflight_url` leer —
     * `.env.local` wird im Test-Env nicht geladen —, und damit wäre Zweig (a)
     * nie erreichbar. Ein Test, der genau deshalb überspringt, belegt das
     * Wichtigste nicht: dass der Beta-Knopf erscheint und der Hinweis für einen
     * toten Link daneben steht. Die Vorlage direkt zu rendern prüft alle drei
     * Zweige, ohne die Umgebung zu verbiegen.
     *
     * @return iterable<string, array{0: AppPlatform, 1: ?string, 2: list<string>, 3: list<string>}>
     */
    public static function mailZweige(): iterable
    {
        yield 'iOS mit Link' => [
            AppPlatform::IOS,
            'https://testflight.apple.com/join/Whxmtrsf',
            ['testflight.apple.com/join/Whxmtrsf', 'Testplätze'],   // Knopf + OF-04-Hinweis
            ['Für Android'],
        ];
        yield 'Android' => [
            AppPlatform::ANDROID,
            null,
            ['Für Android'],                                        // AK-23
            ['testflight.apple.com', 'Testplätze'],
        ];
        yield 'iOS ohne Link' => [
            AppPlatform::IOS,
            null,
            [],
            ['testflight.apple.com', 'href=""', 'Testplätze'],      // AK-24: kein toter Knopf
        ];
    }

    /**
     * @param list<string> $enthalten
     * @param list<string> $fehlt
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('mailZweige')]
    public function testDieZweiteMailInAllenDreiZweigen(
        AppPlatform $platform,
        ?string $link,
        array $enthalten,
        array $fehlt,
    ): void {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'zweig@example.lu', $platform);

        // ⚠ Die Sprache muss gesetzt werden. Wer die Vorlage direkt rendert, hat
        // keine Anfrage — der Übersetzer nimmt dann `default_locale` (lb), und
        // die Prüfung liefe gegen luxemburgische Texte. Genau dieselbe Falle
        // trägt der Mailversand selbst, dort gelöst durch `->locale()` (BF-10).
        $client->getContainer()->get('translator')->setLocale('de');

        $html = $client->getContainer()->get('twig')->render('email/app/beta_access.html.twig', [
            'entry' => $entry,
            'revokeUrl' => 'https://endlech.lu/de/app/abmelden/'.$entry->getConfirmationToken(),
            'testflightUrl' => $link,
        ]);

        foreach ($enthalten as $text) {
            self::assertStringContainsString($text, $html);
        }
        foreach ($fehlt as $text) {
            self::assertStringNotContainsString($text, $html);
        }

        // AK-30: der Rückweg steht in JEDEM Zweig.
        self::assertStringContainsString('/app/abmelden/', $html);
    }

    // ------------------------------------------------------------- Randfälle

    /** EC-05: Grenzen der Eingabe — die Prüfung greift, bevor die Datenbank es tut. */
    public function testUeberlangeUndSeltsameAdressenWerdenAbgewiesen(): void
    {
        $client = static::createClient();

        foreach ([str_repeat('a', 200).'@example.lu', '🙂@example.lu', 'a@b'] as $eingabe) {
            $this->absenden($client, $eingabe, 'ios');
            self::assertResponseStatusCodeSame(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                "Abgewiesen werden muss: {$eingabe}",
            );
        }

        self::assertSame(0, $this->repository($client)->count([]));
    }

    /** EC-05: Randleerzeichen und Großschreibung dürfen keine zweite Zeile erzeugen. */
    public function testAdresseWirdNormalisiertUndBleibtEindeutig(): void
    {
        $client = static::createClient();
        $this->absenden($client, '  Gleiche@Example.LU  ', 'ios');
        $this->absenden($client, 'gleiche@example.lu', 'android');

        self::assertSame(1, $this->repository($client)->count([]));
    }

    /**
     * EC-06: Zwei gleichzeitige Absendevorgänge derselben Adresse.
     *
     * Der Controller prüft vorher, aber zwei parallele Anfragen können beide an
     * der Prüfung vorbei sein, bevor eine schreibt. Entschieden wird das erst am
     * Unique-Index — hier direkt nachgestellt.
     */
    public function testDatenbankVerhindertDieZweiteZeileAuchOhneControllerpruefung(): void
    {
        $client = static::createClient();
        $this->eintrag($client, 'wettlauf@example.lu', AppPlatform::IOS);

        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        $client->getContainer()->get(EntityManagerInterface::class)->getConnection()->executeStatement(
            'INSERT INTO app_waitlist_entry (email, platform, status, consent_at, locale, created_at, updated_at)
             VALUES (:e, :p, :s, NOW(), :l, NOW(), NOW())',
            ['e' => 'wettlauf@example.lu', 'p' => 'ios', 's' => 'pending', 'l' => 'de'],
        );
    }

    /** EC-01: Der Fehlerfall antwortet als HTML, nicht als Turbo-Stream. */
    public function testFehlerfallAntwortetAlsHtml(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PFAD);
        $client->submit($this->formWithField($crawler, 'app_waitlist[email]'));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringStartsWith(
            'text/html',
            (string) $client->getResponse()->headers->get('Content-Type'),
        );
    }

    // ---------------------------------------------------------------- Hilfen

    private function repository(KernelBrowser $client): AppWaitlistEntryRepository
    {
        return $client->getContainer()->get(AppWaitlistEntryRepository::class);
    }

    private function absenden(
        KernelBrowser $client,
        string $email,
        string $platform,
        string $honeypot = '',
    ): void {
        $crawler = $client->request('GET', self::PFAD);
        $form = $this->formWithField($crawler, 'app_waitlist[email]');
        $form['app_waitlist[email]'] = $email;
        $form['app_waitlist[platform]']->select($platform);
        $form['app_waitlist[consent]']->tick();
        $form['app_waitlist[website]'] = $honeypot;

        $client->submit($form);
    }

    private function eintrag(KernelBrowser $client, string $email, AppPlatform $platform): AppWaitlistEntry
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $entry = new AppWaitlistEntry();
        $entry->setEmail($email);
        $entry->setPlatform($platform);
        $entry->setLocale('de');
        $entry->generateConfirmationToken();

        $em->persist($entry);
        $em->flush();

        return $entry;
    }

    private function bestaetigterEintrag(KernelBrowser $client, string $email): AppWaitlistEntry
    {
        $entry = $this->eintrag($client, $email, AppPlatform::IOS);
        $entry->confirm();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        return $entry;
    }

    /**
     * Setzt `createdAt` zurück — die 7-Tage-Frist misst daran
     * ({@see WaitlistConfirmationService::isExpired()}), und eine eigene
     * Ablaufspalte gibt es bewusst nicht.
     */
    private function altern(KernelBrowser $client, AppWaitlistEntry $entry, string $versatz): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement(
            'UPDATE app_waitlist_entry SET created_at = :d WHERE id = :id',
            ['d' => (new \DateTimeImmutable($versatz))->format('Y-m-d H:i:s'), 'id' => $entry->getId()],
        );
        $em->refresh($entry);
    }
}
