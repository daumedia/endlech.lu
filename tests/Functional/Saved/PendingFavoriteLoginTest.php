<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantRepository;
use App\Saved\PendingFavorite;
use App\Security\PasskeyAuthenticator;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Feature 17, T16: Merken ohne Anmeldung (AK-08 bis AK-10, EC-02 bis EC-04).
 *
 * Gegen das **echte** Anmeldeformular: `loginUser()` löst kein Anmeldeereignis aus und ginge
 * an genau der Stelle vorbei, die hier geprüft wird (wie `OnboardingRedirectTest`).
 *
 * Das Herz der Suche schickt der Gast direkt ab (Token aus der Detailseite, `ziel` = Seite 2 mit
 * Filter) — die Suchkarten bekommen das Herz erst später, der Server prüft `ziel` ohnehin selbst.
 */
final class PendingFavoriteLoginTest extends AbstractWebTestCase
{
    private const string KONTO = 'user@endlech.lu';
    private const string PASSWORT = 'user123';
    private const string SUCHE = '/de/restaurants?wheelchair=1&page=2';

    protected function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        $client->getContainer()->get('limiter.saved_change')->create('konto-'.$this->user($client, self::KONTO)->getId())->reset();
        self::ensureKernelShutdown();
    }

    public function testAk08AnmeldeseiteNenntDasLokalUndNichtsIstGespeichert(): void
    {
        $client = static::createClient();
        $lokal = $this->lokal($client);
        $vorher = $this->alleFavoriten($client);

        $this->herzAlsGast($client, $lokal, self::SUCHE);
        self::assertResponseRedirects(self::LOCALE.'/login', 303);
        $anmeldung = $client->followRedirect();

        $hinweis = $anmeldung->filter('#merkwunsch-hinweis');
        self::assertCount(1, $hinweis, 'AK-08: Über dem Formular steht der Hinweis.');
        self::assertSame('status', $hinweis->attr('role'));
        self::assertSame('Melde dich an, um dir '.$lokal->getName().' zu merken.', trim($hinweis->text()));
        self::assertSame($vorher, $this->alleFavoriten($client), 'AK-08: Bis hierher ist nichts gespeichert.');
    }

    public function testOhneMerkwunschKeinHinweis(): void
    {
        $client = static::createClient();

        $anmeldung = $client->request('GET', self::LOCALE.'/login');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $anmeldung->filter('#merkwunsch-hinweis'));
    }

    public function testAk09NachDerAnmeldungZurueckAufSeite2MitFilterUndKarteGemerkt(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, self::KONTO);
        $lokal = $this->lokal($client);
        $this->herzAlsGast($client, $lokal, self::SUCHE);
        $client->followRedirect();

        $this->anmelden($client, self::KONTO, self::PASSWORT);

        self::assertResponseRedirects();
        self::assertSame(self::SUCHE.'#favorit-'.$lokal->getId(), $this->ohneHost($client));
        self::assertSame(1, $this->zahl($client, $konto, $lokal), 'AK-09: Ein zweiter Tipp ist nicht nötig.');

        $seite = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Gemerkt: '.$lokal->getName(), $seite->text());
        self::assertCount(1, $seite->filter('[data-usage-event-name-value="herz_gesetzt"]'), 'AK-66: Herz gesetzt wird gezählt.');

        // AK-09 mit JavaScript: Turbo folgt der Weiterleitung per fetch und verliert dabei den
        // Anker (im Browser gemessen 2026-11-14: Seite oben, Herz 2000 px tiefer). Die Folgeseite
        // trägt deshalb eine Markierung, an der ein Stimulus-Controller zur Karte rollt.
        $markierung = $seite->filter('[data-controller="scroll-to"]');
        self::assertCount(1, $markierung);
        self::assertSame('favorit-'.$lokal->getId(), $markierung->attr('data-scroll-to-id-value'));
        self::assertNotNull($markierung->attr('data-turbo-temporary'), 'BF-177: sonst rollt jede Rückkehr aus dem Zwischenspeicher erneut.');
    }

    public function testAk09VonDerDetailseiteZurueckMitGefuelltemHerz(): void
    {
        $client = static::createClient();
        $lokal = $this->lokal($client);
        $detail = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $client->submit($detail->filter('#favorit-'.$lokal->getId())->form());
        $client->followRedirect();

        $this->anmelden($client, self::KONTO, self::PASSWORT);

        self::assertSame(self::LOCALE.'/restaurants/'.$lokal->getId().'#favorit-'.$lokal->getId(), $this->ohneHost($client));
        $seite = $client->followRedirect();
        self::assertStringContainsString('Gemerkt: '.$lokal->getName(), $seite->text());
        self::assertSame(
            $lokal->getName().' aus Favoriten entfernen',
            trim($seite->filter('#favorit-knopf-'.$lokal->getId())->text()),
            'AK-09: Das Herz ist gefüllt.',
        );
    }

    public function testAk10ErsterVersuchFalschesPasswortZweiterGelingt(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, self::KONTO);
        $lokal = $this->lokal($client);
        $this->herzAlsGast($client, $lokal, self::SUCHE);
        $client->followRedirect();

        $this->anmelden($client, self::KONTO, 'falsch');
        $zurueck = $client->followRedirect();
        self::assertSame(0, $this->zahl($client, $konto, $lokal));
        self::assertCount(1, $zurueck->filter('#merkwunsch-hinweis'), 'Der Wunsch übersteht den Fehlversuch.');

        $this->anmelden($client, self::KONTO, self::PASSWORT);

        self::assertSame(self::SUCHE.'#favorit-'.$lokal->getId(), $this->ohneHost($client));
        self::assertSame(1, $this->zahl($client, $konto, $lokal));
        self::assertStringContainsString('Gemerkt: '.$lokal->getName(), $client->followRedirect()->text());
    }

    public function testEc02SpaeterUeberDieKopfzeileAngemeldet(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, self::KONTO);
        $lokal = $this->lokal($client);
        $this->herzAlsGast($client, $lokal, self::SUCHE);

        // Er verlässt die Anmeldeseite und kommt später über die Kopfzeile wieder.
        $client->request('GET', self::LOCALE.'/restaurants');
        $client->request('GET', self::LOCALE.'/about');
        $this->anmelden($client, self::KONTO, self::PASSWORT);

        self::assertSame(self::SUCHE.'#favorit-'.$lokal->getId(), $this->ohneHost($client));
        self::assertSame(1, $this->zahl($client, $konto, $lokal));
    }

    public function testEc03GeloeschtesLokalWirdStillVerworfen(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $lokal = (new Restaurant())->setName('Gleich weg')->setCity('Luxembourg');
        $em->persist($lokal);
        $em->flush();
        $id = (int) $lokal->getId();
        $this->herzAlsGast($client, $lokal, self::LOCALE.'/restaurants/'.$id);
        $vorher = $this->alleFavoriten($client);

        // Während er sich anmeldet, nimmt die Verwaltung das Lokal heraus.
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->remove($em->find(Restaurant::class, $id));
        $em->flush();
        $this->anmelden($client, self::KONTO, self::PASSWORT);

        self::assertSame($vorher, $this->alleFavoriten($client), 'EC-03: Nichts wird gespeichert.');
        self::assertSame(self::LOCALE.'/restaurants/'.$id.'#favorit-'.$id, $this->ohneHost($client));
        $client->followRedirect();
        self::assertResponseStatusCodeSame(404, 'EC-03: Die gewöhnliche „Nicht gefunden“-Seite.');

        // Eine Meldung wäre spätestens auf der nächsten Seite zu sehen.
        $naechste = $client->request('GET', self::LOCALE.'/restaurants');
        self::assertStringNotContainsString('Gemerkt', $naechste->text());
        self::assertStringNotContainsString('Zu viele Änderungen', $naechste->text());
        self::assertCount(0, $naechste->filter('[data-usage-event-name-value="herz_gesetzt"]'));
    }

    public function testEc04ZielseiteGewinntGegenDieEinfuehrungUndSieBleibtFaellig(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, self::KONTO);
        $konto->markOnboardingDue();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
        self::assertTrue($konto->isOnboardingPending(), 'Vorbedingung: Die Einführung ist fällig.');
        $lokal = $this->lokal($client);
        $this->herzAlsGast($client, $lokal, self::SUCHE);

        $this->anmelden($client, self::KONTO, self::PASSWORT);

        self::assertSame(self::SUCHE.'#favorit-'.$lokal->getId(), $this->ohneHost($client), 'EC-04: Die Seite des Herzens gewinnt.');
        self::assertSame(1, $this->zahl($client, $konto, $lokal));
        self::assertTrue($this->user($client, self::KONTO)->isOnboardingPending(), 'EC-04: Die Einführung bleibt fällig.');
    }

    public function testErschoepfterDeckelMerktNichtUndSagtEs(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, self::KONTO);
        $lokal = $this->lokal($client);
        // when@test steht auf 10000 — aufgebraucht ist derselbe Zustand wie nach 120 in Produktion.
        $client->getContainer()->get('limiter.saved_change')->create('konto-'.$konto->getId())->consume(10000);
        $this->herzAlsGast($client, $lokal, self::SUCHE);

        $this->anmelden($client, self::KONTO, self::PASSWORT);

        self::assertSame(self::SUCHE.'#favorit-'.$lokal->getId(), $this->ohneHost($client), 'Die Anmeldung selbst gelingt.');
        self::assertSame(0, $this->zahl($client, $konto, $lokal));
        $seite = $client->followRedirect();
        self::assertStringContainsString('Zu viele Änderungen', $seite->text());
        self::assertStringNotContainsString('Gemerkt', $seite->text());
        self::assertNull(
            static::getContainer()->get(PendingFavorite::class)->peek($client->getRequest()->getSession()),
            'Der Wunsch ist trotzdem gelöscht — sonst holte ihn jede spätere Anmeldung nach.',
        );
    }

    /**
     * AK-09 „oder mit Passkey“: Die Zeremonie selbst ist ohne virtuellen Authenticator nicht
     * nachstellbar. Geprüft werden ihre beiden Hälften — `onAuthenticationSuccess` liest dieselbe
     * Zielseite, die das Herz geschrieben hat, und das Anmeldeereignis eines anderen
     * Authenticators löst den Wunsch über den Ereignisverteiler der Firewall ein.
     */
    public function testAk09PasskeyWegNimmtDieselbeZielseiteUndMerkt(): void
    {
        $client = static::createClient();
        $lokal = $this->lokal($client);
        $this->herzAlsGast($client, $lokal, self::SUCHE);
        /** @var Request $anfrage */
        $anfrage = $client->getRequest();
        $container = static::getContainer();
        // Nach der Anfrage: Der Kernel ist neu gestartet, ein vorher geladenes Konto wäre losgelöst.
        $konto = $this->user($client, self::KONTO);

        $passkey = new PasskeyAuthenticator($container->get('router'), $container->get('translator'));
        $token = new PostAuthenticationToken($konto, 'main', $konto->getRoles());
        $antwort = $passkey->onAuthenticationSuccess($anfrage, $token, 'main');

        self::assertInstanceOf(RedirectResponse::class, $antwort);
        self::assertSame(self::SUCHE.'#favorit-'.$lokal->getId(), $antwort->getTargetUrl());

        $ereignis = new LoginSuccessEvent(
            $passkey,
            new SelfValidatingPassport(new UserBadge($konto->getUserIdentifier(), static fn (): User => $konto)),
            $token,
            $anfrage,
            $antwort,
            'main',
        );
        // Wie in der echten Anfrage: Der Sitzungs-Zuhörer der Firewall greift über den Stack zu.
        $container->get('request_stack')->push($anfrage);
        try {
            $container->get('security.event_dispatcher.main')->dispatch($ereignis);
        } finally {
            $container->get('request_stack')->pop();
        }

        self::assertSame(1, $this->zahl($client, $konto, $lokal));
        self::assertSame($antwort, $ereignis->getResponse(), 'Der Zuhörer ändert die Antwort nicht.');
        $sitzung = $anfrage->getSession();
        self::assertInstanceOf(FlashBagAwareSessionInterface::class, $sitzung);
        self::assertSame(['Gemerkt: '.$lokal->getName()], $sitzung->getFlashBag()->peek('success'));
        self::assertSame(['herz_gesetzt'], $sitzung->getFlashBag()->peek('usage_event'));
        self::assertNull($container->get(PendingFavorite::class)->peek($sitzung), 'Der Wunsch ist eingelöst.');
    }

    private function herzAlsGast(KernelBrowser $client, Restaurant $lokal, string $ziel): void
    {
        $detail = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $token = (string) $detail->filter('#favorit-'.$lokal->getId().' input[name="_token"]')->attr('value');

        $client->request('POST', self::LOCALE.'/restaurants/'.$lokal->getId().'/merken', [
            '_token' => $token,
            'ziel' => $ziel,
        ]);
        self::assertResponseRedirects(self::LOCALE.'/login', 303);
    }

    private function anmelden(KernelBrowser $client, string $email, string $passwort): void
    {
        $anmeldung = $client->request('GET', self::LOCALE.'/login');
        $client->submit($this->formWithField($anmeldung, '_username', ['_username' => $email, '_password' => $passwort]));
    }

    /** Pfad, Abfrage und Anker der Weiterleitung — `form_login` liefert eine absolute Adresse. */
    private function ohneHost(KernelBrowser $client): string
    {
        $teile = parse_url((string) $client->getResponse()->headers->get('Location'));
        self::assertIsArray($teile);

        return ($teile['path'] ?? '')
            .(isset($teile['query']) ? '?'.$teile['query'] : '')
            .(isset($teile['fragment']) ? '#'.$teile['fragment'] : '');
    }

    private function lokal(KernelBrowser $client): Restaurant
    {
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($lokal);

        return $lokal;
    }

    private function zahl(KernelBrowser $client, User $konto, Restaurant $lokal): int
    {
        return (int) $client->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?',
            [$konto->getId(), $lokal->getId()],
        );
    }

    private function alleFavoriten(KernelBrowser $client): int
    {
        return (int) $client->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne('SELECT COUNT(*) FROM favorite');
    }
}
