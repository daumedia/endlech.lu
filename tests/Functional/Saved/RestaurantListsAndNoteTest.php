<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Feature 17, T15: „In Listen“ und „Deine Notiz“ auf der Detailseite.
 */
final class RestaurantListsAndNoteTest extends AbstractWebTestCase
{
    private const string KONTO = 'user@endlech.lu';

    protected function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        foreach ([self::KONTO, 'admin@endlech.lu', 'unverified@endlech.lu'] as $email) {
            $client->getContainer()->get('limiter.saved_change')->create('konto-'.$this->user($client, $email)->getId())->reset();
        }
        self::ensureKernelShutdown();
    }

    public function testAk07GastSiehtWederListenNochNotiz(): void
    {
        $client = static::createClient();
        $seite = $client->request('GET', $this->detail($this->lokal($client)));

        self::assertCount(0, $seite->filter('#in-listen'));
        self::assertCount(0, $seite->filter('#notiz'));
    }

    public function testAk24OhneListeNurDasFeldNeueListe(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);

        $seite = $client->request('GET', $this->detail($this->lokal($client)));

        self::assertCount(0, $seite->filter('#in-listen input[type=checkbox]'));
        self::assertCount(1, $seite->filter('#in-listen input[name="restaurant_lists[newList]"]'));
    }

    public function testAk21Ak22HaekchenSetzenUndUmhaengen(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $erste = $this->liste($client, $konto, 'Erste');
        $zweite = $this->liste($client, $konto, 'Zweite');
        $this->eintrag($client, $erste, $lokal);

        $seite = $client->request('GET', $this->detail($lokal));
        self::assertCount(2, $seite->filter('#in-listen input[type=checkbox]'));
        self::assertCount(1, $seite->filter('#in-listen input[type=checkbox]:checked'));

        $formular = $this->formWithField($seite, 'restaurant_lists[newList]');
        $this->haekchen($formular, $zweite);
        $client->submit($formular);

        self::assertResponseRedirects($this->detail($lokal).'#in-listen', 303);
        self::assertSame(0, $this->eintraege($client, $erste));
        self::assertSame(1, $this->eintraege($client, $zweite));
        $danach = $client->followRedirect();
        self::assertStringContainsString('Listen gespeichert.', $danach->text());
        // AK-51: Turbo verliert den Anker `#in-listen` — die Markierung rollt zurück zum Abschnitt.
        self::assertSame('in-listen', $danach->filter('[data-controller="scroll-to"]')->attr('data-scroll-to-id-value'));
    }

    /**
     * BF-219 (WCAG 1.4.10): Ein Listenname ohne Leerzeichen sprengte „In Listen“ — das `<label>` steht in
     * `li.flex`, und `overflow-wrap: break-word` (am `body`) verkleinert dort die Mindestbreite nicht.
     * 34 Zeichen ergaben bei 320 px eine Seite von 367 px. Die Beschriftung muss schrumpfen dürfen und
     * überall umbrechen; das Kästchen behält seine Größe. Gemessen wird im Browser — dieser Fall hält
     * die Klassen fest, die den Umbruch tragen (wie auf „Gespeichert“, `saved/index.html.twig`).
     */
    public function testBf219LangerListennameBrichtInDerBeschriftungUm(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $liste = $this->liste($client, $konto, str_repeat('Wochenendausflug', 3).'auswahl');

        $seite = $client->request('GET', $this->detail($this->lokal($client)));

        $kaestchen = $seite->filter('#in-listen input[type=checkbox][value="'.$liste->getId().'"]');
        self::assertCount(1, $kaestchen);
        $beschriftung = $seite->filter('#in-listen label[for="'.$kaestchen->attr('id').'"]');
        self::assertCount(1, $beschriftung);
        $klassen = explode(' ', (string) $beschriftung->attr('class'));
        self::assertContains('min-w-0', $klassen);
        self::assertContains('wrap-anywhere', $klassen);
        self::assertContains('shrink-0', explode(' ', (string) $kaestchen->attr('class')));
    }

    public function testAk23NeueListeEnthaeltDasLokalUndWirdGezaehlt(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);

        $seite = $client->request('GET', $this->detail($lokal));
        $client->submit($this->formWithField($seite, 'restaurant_lists[newList]', ['restaurant_lists[newList]' => 'Mit Oma']));

        $id = (int) $this->db($client)->fetchOne('SELECT id FROM saved_list WHERE user_id = ? AND name = ?', [$konto->getId(), 'Mit Oma']);
        self::assertGreaterThan(0, $id);
        self::assertSame(1, (int) $this->db($client)->fetchOne('SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ? AND restaurant_id = ?', [$id, $lokal->getId()]));
        self::assertStringContainsString('data-usage-event-name-value="liste_angelegt"', (string) $client->followRedirect()->html());
    }

    public function testAk23DubletteAufDerDetailseiteIst422OhneHaekchenAenderung(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $erste = $this->liste($client, $konto, 'Erste');

        $seite = $client->request('GET', $this->detail($lokal));
        $formular = $this->formWithField($seite, 'restaurant_lists[newList]', ['restaurant_lists[newList]' => ' erste ']);
        $this->haekchen($formular, $erste);
        $antwort = $client->submit($formular);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Eine Liste mit diesem Namen hast du schon.', $antwort->filter('#in-listen')->text());
        self::assertNotNull($antwort->filter('input[name="restaurant_lists[newList]"]')->attr('autofocus'), 'AK-50: Fokus im Feld.');
        self::assertSame(0, $this->eintraege($client, $erste));
        // AK-18: Die Eingabe bleibt stehen — ohne den Randleerraum, der laut AK-18 nicht zählt.
        self::assertSame('erste', $antwort->filter('input[name="restaurant_lists[newList]"]')->attr('value'));
    }

    public function testAk20SechsundzwanzigsteListeUeberDieDetailseite(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        for ($i = 1; $i <= SavedList::MAX_PER_USER; ++$i) {
            $this->liste($client, $konto, 'Liste '.$i);
        }

        $seite = $client->request('GET', $this->detail($this->lokal($client)));
        $antwort = $client->submit($this->formWithField($seite, 'restaurant_lists[newList]', ['restaurant_lists[newList]' => 'Liste 26']));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Du hast bereits 25 Listen.', $antwort->text());
        self::assertSame(25, (int) $this->db($client)->fetchOne('SELECT COUNT(*) FROM saved_list WHERE user_id = ?', [$konto->getId()]));
    }

    public function testAk31DoppeltesAbsendenErgibtEinenEintrag(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $liste = $this->liste($client, $konto, 'Kirchberg');

        $seite = $client->request('GET', $this->detail($lokal));
        $formular = $this->formWithField($seite, 'restaurant_lists[newList]');
        $this->haekchen($formular, $liste);
        $client->submit($formular);
        $client->submit($formular);

        self::assertSame(1, $this->eintraege($client, $liste));
    }

    public function testAk54FremdeListennummerIst404UndDieListeBleibtUnberuehrt(): void
    {
        $client = static::createClient();
        $fremd = $this->user($client, 'admin@endlech.lu');
        $lokal = $this->lokal($client);
        $fremdeListe = $this->liste($client, $fremd, 'Geheim');
        $this->loginAs($client, self::KONTO);

        $seite = $client->request('GET', $this->detail($lokal));
        $token = $seite->filter('input[name="restaurant_lists[_token]"]')->attr('value');
        $client->request('POST', $this->detail($lokal).'/listen', ['restaurant_lists' => [
            'lists' => [(string) $fremdeListe->getId()],
            'newList' => '',
            '_token' => $token,
        ]]);

        self::assertResponseStatusCodeSame(404);
        self::assertStringNotContainsString('Geheim', (string) $client->getResponse()->getContent());
        self::assertSame(0, $this->eintraege($client, $fremdeListe));
    }

    public function testAk32Ak33NotizSpeichernUndWiederfinden(): void
    {
        $client = static::createClient();
        $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);

        $seite = $client->request('GET', $this->detail($lokal));
        self::assertStringContainsString('Nur für dich sichtbar.', $seite->filter('#notiz')->text());
        $client->submit($this->formWithField($seite, 'restaurant_note[text]', ['restaurant_note[text]' => 'Seiteneingang links, Rampe steil']));

        self::assertResponseRedirects($this->detail($lokal).'#notiz', 303);
        $danach = $client->followRedirect();
        self::assertStringContainsString('Notiz gespeichert.', $danach->text());
        self::assertSame('notiz', $danach->filter('[data-controller="scroll-to"]')->attr('data-scroll-to-id-value'), 'AK-51: zurück zum Abschnitt.');
        self::assertSame('Seiteneingang links, Rampe steil', $danach->filter('textarea[name="restaurant_note[text]"]')->text());
    }

    public function testAk35ZuLangeNotizIst422UndDieAlteBleibt(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $this->notiz($client, $konto, $lokal, 'alt');

        $seite = $client->request('GET', $this->detail($lokal));
        $antwort = $client->submit($this->formWithField($seite, 'restaurant_note[text]', ['restaurant_note[text]' => str_repeat('x', 501)]));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Die Notiz darf höchstens 500 Zeichen lang sein.', $antwort->filter('#notiz')->text());
        self::assertSame(str_repeat('x', 501), $antwort->filter('textarea[name="restaurant_note[text]"]')->text());
        // AK-50: Meldung am Feld, mit ihm verknüpft, Fokus im Feld.
        $feld = $antwort->filter('textarea[name="restaurant_note[text]"]');
        self::assertNotNull($feld->attr('autofocus'));
        self::assertSame('true', $feld->attr('aria-invalid'));
        self::assertStringContainsString((string) $feld->attr('id').'_error', (string) $feld->attr('aria-describedby'));
        self::assertSame('alt', $this->db($client)->fetchOne('SELECT text FROM restaurant_note WHERE user_id = ? AND restaurant_id = ?', [$konto->getId(), $lokal->getId()]));
    }

    public function testAk36LeerenLoeschtDieNotiz(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $this->notiz($client, $konto, $lokal, 'weg damit');

        $seite = $client->request('GET', $this->detail($lokal));
        $client->submit($this->formWithField($seite, 'restaurant_note[text]', ['restaurant_note[text]' => '']));

        self::assertStringContainsString('Notiz gelöscht.', $client->followRedirect()->text());
        self::assertFalse($this->db($client)->fetchOne('SELECT text FROM restaurant_note WHERE user_id = ?', [$konto->getId()]));
    }

    public function testAk37NotizMitHtmlBleibtText(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $this->notiz($client, $konto, $lokal, "<b>fett</b> <script>alert(1)</script>\nzweite Zeile");

        $client->request('GET', $this->detail($lokal));
        $html = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('&lt;b&gt;fett&lt;/b&gt; &lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function testAk52KontoBSiehtNotizUndListenVonANicht(): void
    {
        $client = static::createClient();
        $a = $this->user($client, 'admin@endlech.lu');
        $lokal = $this->lokal($client);
        $this->eintrag($client, $this->liste($client, $a, 'Mit Oma'), $lokal);
        $this->notiz($client, $a, $lokal, 'Nur für A gedacht');
        $this->loginAs($client, self::KONTO);

        $client->request('GET', $this->detail($lokal));
        $html = (string) $client->getResponse()->getContent();

        self::assertStringNotContainsString('Mit Oma', $html);
        self::assertStringNotContainsString('Nur für A gedacht', $html);
    }

    public function testAk57DeckelErschoepftSpeichertNichts(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, self::KONTO);
        $lokal = $this->lokal($client);
        $seite = $client->request('GET', $this->detail($lokal));
        $client->getContainer()->get('limiter.saved_change')->create('konto-'.$konto->getId())->consume(10000);

        $client->submit($this->formWithField($seite, 'restaurant_note[text]', ['restaurant_note[text]' => 'zu viel']));

        self::assertStringContainsString('Zu viele Änderungen', $client->followRedirect()->text());
        self::assertFalse($this->db($client)->fetchOne('SELECT text FROM restaurant_note WHERE user_id = ?', [$konto->getId()]));
    }

    public function testAk69UnbestaetigtesKontoDarfNotizUndListe(): void
    {
        $client = static::createClient();
        $konto = $this->loginAs($client, 'unverified@endlech.lu');
        $lokal = $this->lokal($client);

        $seite = $client->request('GET', $this->detail($lokal));
        $client->submit($this->formWithField($seite, 'restaurant_note[text]', ['restaurant_note[text]' => 'geht']));
        $seite = $client->request('GET', $this->detail($lokal));
        $client->submit($this->formWithField($seite, 'restaurant_lists[newList]', ['restaurant_lists[newList]' => 'Auch']));

        self::assertSame('geht', $this->db($client)->fetchOne('SELECT text FROM restaurant_note WHERE user_id = ?', [$konto->getId()]));
        self::assertSame(1, (int) $this->db($client)->fetchOne('SELECT COUNT(*) FROM saved_list WHERE user_id = ?', [$konto->getId()]));
    }

    public function testGastWirdZurAnmeldungGeschicktUndNichtsEntsteht(): void
    {
        $client = static::createClient();
        $lokal = $this->lokal($client);

        $client->request('POST', $this->detail($lokal).'/notiz', ['restaurant_note' => ['text' => 'x']]);

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    private function detail(Restaurant $lokal): string
    {
        return self::LOCALE.'/restaurants/'.$lokal->getId();
    }

    private function lokal(KernelBrowser $client): Restaurant
    {
        $lokal = $client->getContainer()->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($lokal);

        return $lokal;
    }

    private function liste(KernelBrowser $client, User $konto, string $name): SavedList
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $liste = new SavedList($konto, $name, mb_strtolower($name));
        $em->persist($liste);
        $em->flush();

        return $liste;
    }

    private function eintrag(KernelBrowser $client, SavedList $liste, Restaurant $lokal): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist(new SavedListEntry($liste, $lokal));
        $em->flush();
    }

    private function notiz(KernelBrowser $client, User $konto, Restaurant $lokal, string $text): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist(new RestaurantNote($konto, $lokal, $text));
        $em->flush();
    }

    private function eintraege(KernelBrowser $client, SavedList $liste): int
    {
        return (int) $this->db($client)->fetchOne('SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ?', [$liste->getId()]);
    }

    private function db(KernelBrowser $client): \Doctrine\DBAL\Connection
    {
        return $client->getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    /**
     * Setzt genau das Häkchen dieser Liste und nimmt alle anderen heraus.
     */
    private function haekchen(\Symfony\Component\DomCrawler\Form $formular, SavedList $liste): void
    {
        $felder = $formular['restaurant_lists[lists]'];
        foreach (\is_array($felder) ? $felder : [$felder] as $feld) {
            \assert($feld instanceof \Symfony\Component\DomCrawler\Field\ChoiceFormField);
            if (\in_array((string) $liste->getId(), $feld->availableOptionValues(), true)) {
                $feld->tick();
            } else {
                $feld->untick();
            }
        }
    }
}
