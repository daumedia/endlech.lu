<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Feature 12 — wohin die Anmeldung führt (AK-01 bis AK-04, AK-06, EC-06).
 *
 * Gegen das **echte** Anmeldeformular: `loginUser()` löst kein Anmeldeereignis aus und ginge
 * damit an genau der Stelle vorbei, die hier geprüft wird.
 */
final class OnboardingRedirectTest extends AbstractWebTestCase
{
    private function anmelden(KernelBrowser $client, bool $angemeldetBleiben = false): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/login');
        $werte = ['_username' => 'user@endlech.lu', '_password' => 'user123'];
        if ($angemeldetBleiben) {
            $werte['_remember_me'] = 'on';
        }
        $client->submit($this->formWithField($crawler, '_password', $werte));
    }

    private function vorgesehen(KernelBrowser $client): void
    {
        $user = $this->user($client, 'user@endlech.lu');
        $user->markOnboardingDue();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();
    }

    public function testAk01ErsteAnmeldungFuehrtInDieEinfuehrung(): void
    {
        $client = static::createClient();
        $this->vorgesehen($client);

        $this->anmelden($client);

        // Der Router lässt den Vorgabeschritt weg: `/de/einfuehrung` IST die Willkommensseite.
        self::assertResponseRedirects(self::LOCALE.'/einfuehrung');
    }

    public function testAk02NachDerAnzeigeFuehrtDieAnmeldungAufDieStartseite(): void
    {
        $client = static::createClient();
        $this->vorgesehen($client);
        $user = $this->user($client, 'user@endlech.lu');
        $user->markOnboardingShown(new \DateTimeImmutable());
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $this->anmelden($client);

        self::assertResponseRedirects();
        self::assertSame('/de', rtrim((string) parse_url((string) $client->getResponse()->headers->get('Location'), PHP_URL_PATH), '/'));
    }

    public function testAk03GemerkteZielseiteHatVorrang(): void
    {
        $client = static::createClient();
        $this->vorgesehen($client);

        // B11/AK-01: Die Erklärseite merkt sich, wohin es nach der Anmeldung geht.
        $client->request('GET', self::LOCALE.'/community/suggest');
        $this->anmelden($client);

        self::assertResponseRedirects();
        self::assertStringEndsWith('/community/suggest', (string) $client->getResponse()->headers->get('Location'));
        self::assertTrue($this->user($client, 'user@endlech.lu')->isOnboardingPending(), 'Die Einführung gilt danach als NICHT gesehen.');
    }

    public function testAk04UnbestaetigtesKontoBekommtSieNicht(): void
    {
        $client = static::createClient();
        $user = $this->user($client, 'user@endlech.lu');
        $user->markOnboardingDue();
        $user->setIsVerified(false);
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $this->anmelden($client);

        self::assertSame('/de', rtrim((string) parse_url((string) $client->getResponse()->headers->get('Location'), PHP_URL_PATH), '/'));
    }

    public function testAk06BestandskontoBekommtSieNicht(): void
    {
        $client = static::createClient();

        $this->anmelden($client);

        self::assertSame('/de', rtrim((string) parse_url((string) $client->getResponse()->headers->get('Location'), PHP_URL_PATH), '/'));
    }

    public function testEc06AngemeldetBleibenLenktNichtUm(): void
    {
        $client = static::createClient();
        $this->anmelden($client, angemeldetBleiben: true);
        $cookie = $client->getCookieJar()->get('REMEMBERME');
        self::assertNotNull($cookie, 'Ohne „angemeldet bleiben" gäbe es nichts zu prüfen.');

        // Erst danach fällig — so ist sicher, dass die Umlenkung aus dem automatischen
        // Wiederanmelden käme und nicht aus der Anmeldung eben.
        $this->vorgesehen($client);
        $client->getCookieJar()->clear();
        $client->getCookieJar()->set($cookie);

        $client->request('GET', self::LOCALE.'/profile');

        self::assertResponseIsSuccessful();
        self::assertTrue($this->user($client, 'user@endlech.lu')->isOnboardingPending());
    }
}
