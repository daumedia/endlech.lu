<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Symfony\Component\Routing\RouterInterface;

/**
 * Feature 17, T26: Listen und Notizen bleiben beim Konto.
 *
 * - AK-59: Keine Seite der Verwaltung zeigt Favoriten, Listennamen oder Notizen eines Kontos.
 * - AK-64: Beim Anlegen einer Liste und Speichern einer Notiz enthält kein Protokolleintrag, keine
 *   Mail und kein Auftrag an Brevo den Listennamen oder den Notiztext.
 */
final class SavedPrivacyLeakTest extends AbstractWebTestCase
{
    private const string LISTE = 'Geheimliste Siebzehn';
    private const string NOTIZ = 'Notiztext-Siebzehn mit Rampe für Papa';

    protected function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        $client->getContainer()->get('limiter.saved_change')->create('konto-'.$this->user($client, 'user@endlech.lu')->getId())->reset();
        self::ensureKernelShutdown();
    }

    public function testAk59KeineVerwaltungsseiteZeigtListenOderNotizen(): void
    {
        $client = static::createClient();
        $konto = $this->user($client, 'user@endlech.lu');
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertInstanceOf(Restaurant::class, $lokal);
        $liste = new SavedList($konto, self::LISTE, mb_strtolower(self::LISTE));
        $em->persist($liste);
        $em->persist(new SavedListEntry($liste, $lokal));
        $em->persist(new Favorite($konto, $lokal));
        $em->persist(new RestaurantNote($konto, $lokal, self::NOTIZ));
        $em->flush();
        $this->loginAs($client, 'admin@endlech.lu');

        $geprueft = [];
        foreach ($this->verwaltungsseiten($client, (int) $lokal->getId(), (int) $konto->getId()) as $pfad) {
            $client->request('GET', $pfad);
            $status = $client->getResponse()->getStatusCode();
            if ($status >= 300) {
                continue;
            }
            $html = (string) $client->getResponse()->getContent();
            self::assertStringNotContainsString(self::LISTE, $html, $pfad);
            self::assertStringNotContainsString(self::NOTIZ, $html, $pfad);
            self::assertStringNotContainsString('Notiztext-Siebzehn', $html, $pfad);
            $geprueft[] = $pfad;
        }

        self::assertGreaterThan(5, \count($geprueft), 'Der Lauf hat kaum Verwaltungsseiten gesehen: '.implode(', ', $geprueft));
    }

    public function testAk64ListennameUndNotizErreichenWederProtokollNochMailNochBrevo(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $kontakteVorher = (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM marketing_contact');
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertInstanceOf(Restaurant::class, $lokal);

        $zeilen = 0;
        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $client->submit($this->formWithField($seite, 'restaurant_lists[newList]', ['restaurant_lists[newList]' => self::LISTE]));
        self::assertEmailCount(0);
        $zeilen += $this->pruefeProtokoll($client);

        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $client->submit($this->formWithField($seite, 'restaurant_note[text]', ['restaurant_note[text]' => self::NOTIZ]));
        self::assertEmailCount(0);
        $zeilen += $this->pruefeProtokoll($client);

        // Und eine abgewiesene Notiz (zu lang) — auch der Fehlerweg schreibt den Text nirgends hin.
        $seite = $client->request('GET', self::LOCALE.'/restaurants/'.$lokal->getId());
        $client->submit($this->formWithField($seite, 'restaurant_note[text]', ['restaurant_note[text]' => self::NOTIZ.str_repeat('x', 500)]));
        self::assertResponseStatusCodeSame(422);
        $zeilen += $this->pruefeProtokoll($client);

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        self::assertSame(1, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM saved_list WHERE name = ?', [self::LISTE]), 'Die Liste wurde angelegt — sonst prüfte der Lauf ins Leere.');
        self::assertSame($kontakteVorher, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM marketing_contact'), 'Kein Auftrag an Brevo.');
        self::assertGreaterThan(0, $zeilen, 'Der Speicher-Handler hat nichts gesehen — der Lauf prüfte ins Leere.');
    }

    public function testAk64ProduktionProtokolliertKeinSql(): void
    {
        $konfiguration = \Symfony\Component\Yaml\Yaml::parseFile(
            static::getContainer()->getParameter('kernel.project_dir').'/config/packages/doctrine.yaml',
        );

        self::assertFalse(
            $konfiguration['when@prod']['doctrine']['dbal']['logging'] ?? null,
            'Mit SQL-Protokoll in Produktion stünde der Notiztext bei jedem Fehler in stderr.',
        );
    }

    private function pruefeProtokoll(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): int
    {
        /** @var TestHandler $speicher */
        $speicher = $client->getContainer()->get('monolog.handler.pruefung');
        $zeilen = 0;
        foreach ($speicher->getRecords() as $eintrag) {
            // Die SQL-Zeilen des Kanals `doctrine` gibt es nur im Debug-Betrieb (Test, dev). In
            // Produktion ist das DBAL-Protokoll aus — das hält `testAk64ProduktionProtokolliertKeinSql` fest.
            if ('doctrine' === $eintrag->channel) {
                continue;
            }
            ++$zeilen;
            $text = $eintrag->message.' '.json_encode($eintrag->context).' '.json_encode($eintrag->extra);
            self::assertStringNotContainsString(self::LISTE, $text, $eintrag->message);
            self::assertStringNotContainsString('Notiztext-Siebzehn', $text, $eintrag->message);
        }

        return $zeilen;
    }

    /**
     * Alle GET-Routen der Verwaltung, die sich mit den Nummern dieses Laufs füllen lassen.
     *
     * @return list<string>
     */
    private function verwaltungsseiten(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, int $lokal, int $konto): array
    {
        $router = $client->getContainer()->get(RouterInterface::class);
        $pfade = [];
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            if (!str_starts_with($name, 'admin_') || ([] !== $route->getMethods() && !\in_array('GET', $route->getMethods(), true))) {
                continue;
            }
            $parameter = ['_locale' => 'de'];
            foreach ($route->compile()->getPathVariables() as $variable) {
                if ('_locale' === $variable) {
                    continue;
                }
                $parameter[$variable] = 'id' === $variable ? $lokal : $konto;
            }
            try {
                $pfade[] = $router->generate($name, $parameter);
            } catch (\Throwable) {
                // Route mit Anforderungen, die diese Nummern nicht erfüllen — übergehen.
            }
        }

        return array_values(array_unique($pfade));
    }
}
