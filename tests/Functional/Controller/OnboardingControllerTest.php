<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Feature 12 — die vier Bildschirme der Einführung.
 */
final class OnboardingControllerTest extends AbstractWebTestCase
{
    private const KONTO = 'user@endlech.lu';

    /** @var list<string> */
    private array $aufzuraeumen = [];

    protected function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::KONTO)->reset();
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        foreach ($this->aufzuraeumen as $pfad) {
            @unlink($pfad);
        }
        parent::tearDown();
    }

    private function angemeldet(bool $abonniert = false): KernelBrowser
    {
        $client = static::createClient();
        $user = $this->loginAs($client, self::KONTO);
        $user->setMarketingConsentAt($abonniert ? new \DateTimeImmutable('-1 day') : null);
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        return $client;
    }

    private function seite(KernelBrowser $client, string $schritt = ''): Crawler
    {
        return $client->request('GET', self::LOCALE.'/einfuehrung'.('' === $schritt ? '' : '/'.$schritt));
    }

    private function trans(KernelBrowser $client, string $key, array $parameter = []): string
    {
        return $client->getContainer()->get(TranslatorInterface::class)->trans($key, $parameter, null, 'de');
    }

    private function konto(KernelBrowser $client): User
    {
        $client->getContainer()->get(EntityManagerInterface::class)->clear();

        return $this->user($client, self::KONTO);
    }

    private function png(): string
    {
        $pfad = sys_get_temp_dir().'/f12-'.bin2hex(random_bytes(4)).'.png';
        $bild = imagecreatetruecolor(4, 4);
        imagepng($bild, $pfad);
        $this->aufzuraeumen[] = $pfad;

        return $pfad;
    }

    // ── Zugang ───────────────────────────────────────────────────────────────

    public function testAk10GastLandetAufDerAnmeldeseite(): void
    {
        $client = static::createClient();

        $this->seite($client, 'eintraege');

        self::assertResponseRedirects();
        self::assertStringEndsWith('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAk11UnbestaetigtesKontoLandetBeimHinweis(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'unverified@endlech.lu');

        $this->seite($client);

        self::assertResponseRedirects(self::LOCALE.'/verify');
    }

    public function testAk02ErsteAnzeigeWirdVermerkt(): void
    {
        $client = $this->angemeldet();
        self::assertNull($this->konto($client)->getOnboardingShownAt());

        $this->seite($client);

        self::assertResponseIsSuccessful();
        self::assertNotNull($this->konto($client)->getOnboardingShownAt());
    }

    public function testAk09EinfuehrungIstUeberDasProfilErreichbar(): void
    {
        $client = $this->angemeldet();

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $link = $crawler->selectLink($this->trans($client, 'onboarding.profile_link'));

        self::assertCount(1, $link);
        $client->click($link->link());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $this->trans($client, 'onboarding.welcome.title'));
    }

    // ── Ablauf ───────────────────────────────────────────────────────────────

    public function testAk12Ak14ReihenfolgeUndZaehlungUeberWeiter(): void
    {
        $client = $this->angemeldet();
        $crawler = $this->seite($client);
        $titel = [];

        for ($i = 1; $i <= 4; ++$i) {
            self::assertSelectorTextContains('h1', 'Schritt '.$i.' von 4');
            self::assertStringContainsString('Schritt '.$i.' von 4', (string) $crawler->filter('title')->text());
            $titel[] = $crawler->filter('h1')->text();

            if ($i < 4) {
                $crawler = $client->click($crawler->selectLink($this->trans($client, 'onboarding.next'))->link());
            }
        }

        self::assertStringContainsString($this->trans($client, 'onboarding.welcome.title'), $titel[0]);
        self::assertStringContainsString($this->trans($client, 'onboarding.entries.title'), $titel[1]);
        self::assertStringContainsString($this->trans($client, 'onboarding.avatar.title'), $titel[2]);
        self::assertStringContainsString($this->trans($client, 'onboarding.newsletter.title'), $titel[3]);
    }

    public function testAk13AbonnentSiehtDreiSchritteUndKeinenNewsletter(): void
    {
        $client = $this->angemeldet(abonniert: true);

        $crawler = $this->seite($client, 'profilbild');
        self::assertSelectorTextContains('h1', 'Schritt 3 von 3');
        // Nur die Fußleiste der Einführung: Die Seitennavigation trägt einen gleichlautenden Link.
        $fussleiste = $crawler->filter('nav[aria-label="'.$this->trans($client, 'onboarding.nav_label').'"]');
        self::assertCount(1, $fussleiste->selectLink($this->trans($client, 'onboarding.search')), 'Auf dem letzten Bildschirm heißt „Weiter" „Restaurants suchen".');
        self::assertCount(0, $fussleiste->selectLink($this->trans($client, 'onboarding.next')));

        $this->seite($client, 'newsletter');
        self::assertResponseRedirects(self::LOCALE.'/restaurants');
    }

    public function testAk15WeiterIstEinLinkUndSpeichertNichts(): void
    {
        $client = $this->angemeldet();
        $crawler = $this->seite($client, 'profilbild');

        $weiter = $crawler->selectLink($this->trans($client, 'onboarding.next'));
        self::assertStringEndsWith('/einfuehrung/newsletter', (string) $weiter->attr('href'));
        $client->click($weiter->link());

        self::assertResponseIsSuccessful();
        self::assertNull($this->konto($client)->getAvatarFilename());
    }

    public function testAk16ZurueckAbDemZweitenBildschirm(): void
    {
        $client = $this->angemeldet();

        $erster = $this->seite($client);
        self::assertCount(0, $erster->selectLink($this->trans($client, 'onboarding.back')));

        $zweiter = $this->seite($client, 'eintraege');
        self::assertStringEndsWith('/einfuehrung', (string) $zweiter->selectLink($this->trans($client, 'onboarding.back'))->attr('href'));
    }

    public function testAk17EinfuehrungBeendenAufJedemBildschirm(): void
    {
        $client = $this->angemeldet();

        foreach (['willkommen', 'eintraege', 'profilbild', 'newsletter'] as $schritt) {
            $link = $this->seite($client, $schritt)->selectLink($this->trans($client, 'onboarding.finish'));
            self::assertCount(1, $link, $schritt);
            self::assertStringEndsWith('/restaurants', (string) $link->attr('href'), $schritt);
        }
    }

    public function testAk18KopfzeileUndUntereLeisteBleiben(): void
    {
        $client = $this->angemeldet();

        $crawler = $this->seite($client, 'eintraege');

        self::assertGreaterThan(0, $crawler->filter('header a[href$="/restaurants"]')->count(), 'Kopfzeile mit Navigation');
        self::assertGreaterThan(0, $crawler->filter('nav.fixed.bottom-0, nav[class*="bottom-0"]')->count(), 'Untere Leiste auf dem Telefon');
    }

    // ── Willkommen ───────────────────────────────────────────────────────────

    public function testAk20WillkommenNenntNamenUndZweiWege(): void
    {
        $client = $this->angemeldet();

        $crawler = $this->seite($client);

        self::assertStringContainsString($this->konto($client)->getName() ?? '', $crawler->filter('main')->text());
        self::assertGreaterThan(0, $crawler->filter('main a[href$="/community/suggest"]')->count());
        self::assertGreaterThan(0, $crawler->filter('main a[href$="/app"]')->count());
    }

    // ── Einträge lesen ───────────────────────────────────────────────────────

    public function testAk22Ak23Ak64BegriffeStehenInJederSpracheWieAufDerDetailseite(): void
    {
        $client = $this->angemeldet();
        $translator = $client->getContainer()->get(TranslatorInterface::class);

        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            $text = $client->request('GET', '/'.$sprache.'/einfuehrung/eintraege')->filter('main')->text();

            foreach (['verified.badge_text', 'accessibility.not_available', 'accessibility.not_measured', 'accessibility.door_width', 'accessibility.table_spacing', 'onboarding.entries.principle'] as $key) {
                self::assertStringContainsString($translator->trans($key, [], null, $sprache), $text, $sprache.': '.$key);
            }
            self::assertStringContainsString($translator->trans('accessibility.below_minimum', ['%min%' => 90], null, $sprache), $text, $sprache);
        }
    }

    // ── Profilbild ───────────────────────────────────────────────────────────

    public function testAk25BildHochladenSpeichertEs(): void
    {
        $client = $this->angemeldet();
        $crawler = $this->seite($client, 'profilbild');
        $form = $crawler->filter('form[action$="/einfuehrung/profilbild"]')->form();
        $form['onboarding_avatar[avatar]']->upload($this->png());

        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/einfuehrung/profilbild');
        $dateiname = $this->konto($client)->getAvatarFilename();
        self::assertNotNull($dateiname);
        $this->aufzuraeumen[] = static::getContainer()->getParameter('kernel.project_dir').'/public/uploads/avatars/'.$dateiname;

        $crawler = $client->followRedirect();
        self::assertGreaterThan(0, $crawler->filter('header img[src*="'.$dateiname.'"]')->count(), 'Sofort in der Kopfzeile (AK-25).');
        self::assertGreaterThan(0, $crawler->filter('main img[src*="'.$dateiname.'"]')->count(), 'Und auf diesem Bildschirm (AK-29).');
    }

    public function testAk27UmbenannteTextdateiWirdMit422Abgewiesen(): void
    {
        $client = $this->angemeldet();
        $pfad = sys_get_temp_dir().'/f12-kein-bild.jpg';
        file_put_contents($pfad, "kein Bild\n");
        $this->aufzuraeumen[] = $pfad;
        $form = $this->seite($client, 'profilbild')->filter('form[action$="/einfuehrung/profilbild"]')->form();
        $form['onboarding_avatar[avatar]']->upload($pfad);

        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString(
            $client->getContainer()->get(TranslatorInterface::class)->trans('profile.avatar_mime_type', [], 'validators', 'de'),
            $crawler->filter('#onboarding_avatar_avatar_error')->text(),
        );
        self::assertNull($this->konto($client)->getAvatarFilename());
        self::assertCount(1, $crawler->selectLink($this->trans($client, 'onboarding.next')), '„Weiter" bleibt möglich (AK-26).');
    }

    public function testAk28HinweisZurSichtbarkeit(): void
    {
        $client = $this->angemeldet();

        self::assertStringContainsString($this->trans($client, 'onboarding.avatar.visibility'), $this->seite($client, 'profilbild')->filter('main')->text());
    }

    // ── Newsletter ───────────────────────────────────────────────────────────

    public function testAk30HaekchenLeer(): void
    {
        $client = $this->angemeldet();

        $crawler = $this->seite($client, 'newsletter');

        self::assertCount(1, $crawler->filter('input[type=checkbox][name="onboarding_newsletter[consent]"]'));
        self::assertCount(0, $crawler->filter('input[type=checkbox][name="onboarding_newsletter[consent]"][checked]'));
    }

    public function testAk32OhneHaekchenGeschiehtNichts(): void
    {
        $client = $this->angemeldet();
        $form = $this->seite($client, 'newsletter')->filter('#einfuehrung-newsletter')->form();

        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/restaurants');
        self::assertNull($this->konto($client)->getMarketingConsentAt());
        self::assertNull($client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::KONTO));
    }

    public function testAk33MitHaekchenEinwilligungUndAuftrag(): void
    {
        $client = $this->angemeldet();
        $form = $this->seite($client, 'newsletter')->filter('#einfuehrung-newsletter')->form();
        $form['onboarding_newsletter[consent]']->tick();

        $client->submit($form);

        self::assertResponseRedirects(self::LOCALE.'/restaurants');
        self::assertNotNull($this->konto($client)->getMarketingConsentAt());
        $kontakt = $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::KONTO);
        self::assertSame(MarketingSyncState::PENDING, $kontakt?->getSyncState());
        self::assertSame('de', $kontakt?->getLocale());
    }

    // ── Zugriff und Missbrauch ───────────────────────────────────────────────

    public function testAk57FremdeKennungImRumpfAendertNurDasEigeneKonto(): void
    {
        $client = $this->angemeldet();
        $fremd = $this->user($client, 'admin@endlech.lu');
        $fremdVorher = $fremd->getMarketingConsentAt();
        $form = $this->seite($client, 'newsletter')->filter('#einfuehrung-newsletter')->form();

        $client->request('POST', (string) $form->getUri(), [
            'onboarding_newsletter' => [
                'consent' => '1',
                '_token' => $form->get('onboarding_newsletter[_token]')->getValue(),
                'user' => (string) $fremd->getId(),
            ],
            'user' => (string) $fremd->getId(),
        ]);

        self::assertNotNull($this->konto($client)->getMarketingConsentAt());
        self::assertEquals($fremdVorher, $this->user($client, 'admin@endlech.lu')->getMarketingConsentAt());
    }

    public function testAk58OhneGueltigesTokenAendertSichNichts(): void
    {
        $client = $this->angemeldet();

        $client->request('POST', self::LOCALE.'/einfuehrung/newsletter', [
            'onboarding_newsletter' => ['consent' => '1', '_token' => 'gefaelscht'],
        ], server: ['HTTP_REFERER' => 'https://fremde-seite.example/']);

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->konto($client)->getMarketingConsentAt());
    }

    public function testAk59ErschoepftesKontingentAendertNichts(): void
    {
        $client = $this->angemeldet();
        /** @var RateLimiterFactoryInterface $factory */
        $factory = $client->getContainer()->get('limiter.newsletter_toggle');
        $factory->create(self::KONTO)->consume(10000);
        $form = $this->seite($client, 'newsletter')->filter('#einfuehrung-newsletter')->form();
        $form['onboarding_newsletter[consent]']->tick();

        $client->submit($form);
        $crawler = $client->followRedirect();

        self::assertStringEndsWith('/einfuehrung/newsletter', (string) $client->getRequest()->getUri());
        self::assertStringContainsString('Zu viele Änderungen', $crawler->text());
        self::assertNull($this->konto($client)->getMarketingConsentAt());
        self::assertNull($client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::KONTO), 'Nichts geht an Brevo.');
    }

    public function testAk63KeinBrevoSchluesselImQuelltext(): void
    {
        // Mit einem Schlüssel in der Umgebung, damit es etwas zu finden gäbe.
        $_SERVER['BREVO_API_KEY'] = $_ENV['BREVO_API_KEY'] = 'xkeysib-f12-pruefschluessel';
        try {
            $client = $this->angemeldet();
            self::assertSame('xkeysib-f12-pruefschluessel', $client->getContainer()->getParameter('app.brevo_api_key'));

            foreach (['/einfuehrung', '/einfuehrung/eintraege', '/einfuehrung/profilbild', '/einfuehrung/newsletter', '/profile'] as $pfad) {
                $client->request('GET', self::LOCALE.$pfad);
                $html = (string) $client->getResponse()->getContent();
                self::assertStringNotContainsString('xkeysib-', $html, $pfad);
                self::assertStringNotContainsString('f12-pruefschluessel', $html, $pfad);
            }
        } finally {
            unset($_SERVER['BREVO_API_KEY'], $_ENV['BREVO_API_KEY']);
        }
    }

    public function testAk60NurEinWechselVerbrauchtKontingent(): void
    {
        $client = $this->angemeldet();
        $factory = $client->getContainer()->get('limiter.newsletter_toggle');
        $vorher = $factory->create(self::KONTO)->consume(0)->getRemainingTokens();

        $form = $this->seite($client, 'newsletter')->filter('#einfuehrung-newsletter')->form();
        $client->submit($form);
        self::assertSame($vorher, $factory->create(self::KONTO)->consume(0)->getRemainingTokens(), 'Ohne Häkchen kein Verbrauch.');

        $form = $this->seite($client, 'newsletter')->filter('#einfuehrung-newsletter')->form();
        $form['onboarding_newsletter[consent]']->tick();
        $client->submit($form);
        self::assertSame($vorher - 1, $factory->create(self::KONTO)->consume(0)->getRemainingTokens(), 'Ein Wechsel, ein Verbrauch.');
    }
}
