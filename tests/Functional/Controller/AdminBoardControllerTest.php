<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Account\AccountDataExporter;
use App\Account\AccountDeleter;
use App\Board\BoardModerator;
use App\Board\BoardVoteService;
use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Repository\BoardIdeaRepository;
use App\Repository\BoardVoteRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Moderation, Benachrichtigung und die Betroffenenrechte am Board (Feature 06).
 */
final class AdminBoardControllerTest extends AbstractWebTestCase
{
    use MailerAssertionsTrait;

    private function idee(KernelBrowser $client, bool $published = false, ?User $von = null, string $titel = 'Kartenansicht'): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung zur Idee.')
            ->setSlug('idee')
            ->setLocale('de')
            ->setSubmittedBy($von ?? $this->user($client, 'user@endlech.lu'));

        if ($published) {
            $idee->setPublishedAt(new \DateTimeImmutable());
        }

        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    /** Lädt die Idee frisch aus der Datenbank — nie aus der Identity Map. */
    private function frisch(KernelBrowser $client, BoardIdea $idea): ?BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->getRepository(BoardIdea::class)->find($idea->getId());
    }

    /**
     * Schickt einen Moderationsweg ab — über das GERENDERTE Formular.
     *
     * ⚠ Die Token dieser Wege sind session-basiert (eigene Token-ID je Vorgang)
     * und lassen sich außerhalb eines Requests nicht erzeugen. Sie aus der Seite
     * zu nehmen ist zugleich der echte Nutzerweg.
     *
     * @param array<string, string> $daten
     */
    private function post(KernelBrowser $client, BoardIdea $idea, string $weg, array $daten = []): void
    {
        $crawler = $client->request('GET', self::LOCALE . '/admin/ideen/' . $idea->getId());
        self::assertResponseIsSuccessful();

        $client->submit($this->formByAction($crawler, '/' . $weg, $daten));
    }

    /** AK-29 + AK-57: Ohne Adminrechte kein Zugriff und keine Zustandsänderung. */
    public function testNichtadminBekommt403(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $idee = $this->idee($client);

        $client->request('GET', self::LOCALE . '/admin/ideen');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Direkt gepokt, ohne Token: `#[IsGranted]` greift VOR der
        // CSRF-Prüfung — die Antwort ist 403, nicht eine Weiterleitung.
        $client->request('POST', self::LOCALE . '/admin/ideen/' . $idee->getId() . '/veroeffentlichen');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        self::assertFalse($this->frisch($client, $idee)->isPublished(), 'Der Status darf sich nicht geändert haben.');
    }

    /** AK-26 + AK-37: Freigabe macht öffentlich und schickt GENAU EINE Mail. */
    public function testFreigabeVeroeffentlichtUndSchicktEineMail(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $idee = $this->idee($client);

        $this->post($client, $idee, 'veroeffentlichen');

        self::assertTrue($this->frisch($client, $idee)->isPublished());
        self::assertEmailCount(1);
    }

    /** AK-38 + EC-05: Ein zweiter Versuch wirkt nicht und schickt keine zweite Mail. */
    public function testZweiteFreigabeWirktNichtUndSchicktKeineZweiteMail(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $idee = $this->idee($client);

        $this->post($client, $idee, 'veroeffentlichen');

        // ⚠ Die Oberfläche bietet den Weg danach gar nicht mehr an — das ist
        // die Höflichkeit. Die Regel sitzt im Dienst, und genau dort wird sie
        // geprüft: Ein zweiter Aufruf wirkt nicht und schickt keine Mail.
        $frisch = $this->frisch($client, $idee);
        self::assertFalse(
            $client->getContainer()->get(BoardModerator::class)->publish($frisch),
            'Eine zweite Freigabe darf nicht wirken (EC-05).',
        );

        self::assertEmailCount(1);
    }

    /** AK-27: Eine Ablehnung ohne Begründung wird nicht ausgeführt. */
    public function testAblehnungOhneBegruendungGeschiehtNicht(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $idee = $this->idee($client);

        $this->post($client, $idee, 'ablehnen', ['reason' => '   ']);

        $nachher = $this->frisch($client, $idee);
        self::assertFalse($nachher->isPublished());
        self::assertNotSame(BoardIdeaStatus::DECLINED, $nachher->getStatus());
        self::assertNull($nachher->getTeamResponse());
    }

    /** AK-28: Eine abgelehnte Idee bleibt öffentlich sichtbar — mit Begründung. */
    public function testAbgelehnteIdeeBleibtSichtbar(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $idee = $this->idee($client, titel: 'Wird abgelehnt');

        $this->post($client, $idee, 'ablehnen', ['reason' => 'Passt nicht zum Zweck der Plattform.']);

        $nachher = $this->frisch($client, $idee);
        self::assertSame(BoardIdeaStatus::DECLINED, $nachher->getStatus());
        self::assertTrue($nachher->isPublished(), 'Eine Ablehnung ist eine Veröffentlichung.');

        $client->request('GET', self::LOCALE . '/community/ideen?status=declined');
        self::assertSelectorTextContains('body', 'Wird abgelehnt');
        self::assertSelectorTextContains('body', 'Passt nicht zum Zweck der Plattform.');
    }

    /** AK-30: Eine veröffentlichte Idee lässt sich nicht löschen. */
    public function testVeroeffentlichteIdeeLaesstSichNichtLoeschen(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $idee = $this->idee($client, published: true);

        // Die Verwaltung bietet den Knopf für eine veröffentlichte Idee nicht an.
        // Entscheidend ist aber die serverseitige Regel — „es gibt keinen Knopf"
        // ist keine Regel.
        self::assertFalse($client->getContainer()->get(BoardModerator::class)->delete($idee));
        self::assertSame(1, $client->getContainer()->get(BoardIdeaRepository::class)->count([]));
    }

    /** AK-34: Beim Zusammenführen zählt ein doppelt stimmendes Konto einmal. */
    public function testZusammenfuehrenZaehltDoppelteStimmeEinmal(): void
    {
        $client = static::createClient();
        $admin = $this->loginAs($client, 'admin@endlech.lu');
        $nutzer = $this->user($client, 'user@endlech.lu');

        $original = $this->idee($client, published: true, titel: 'Original');
        $dublette = $this->idee($client, published: true, titel: 'Dublette');

        $voting = $client->getContainer()->get(BoardVoteService::class);
        $voting->toggle($original, $admin);      // nur Original
        $voting->toggle($original, $nutzer);     // beide …
        $voting->toggle($dublette, $nutzer);     // … dasselbe Konto

        $this->post($client, $dublette, 'dublette', ['target' => (string) $original->getId()]);

        $repo = $client->getContainer()->get(BoardIdeaRepository::class);
        self::assertSame(2, $repo->countVotesForOne($original), 'Das doppelt stimmende Konto darf nur einmal zählen.');
    }

    /** AK-25: Die Zahl wartender Ideen steht im Dashboard. */
    public function testDashboardZeigtWartendeIdeen(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');
        $this->idee($client);
        $this->idee($client, titel: 'Zweite');

        $client->request('GET', self::LOCALE . '/admin');

        self::assertResponseIsSuccessful();
        self::assertSame(2, $client->getContainer()->get(BoardIdeaRepository::class)->countAwaitingReview());
    }

    /**
     * AK-65, AK-66, EC-09: Die Kontolöschung nimmt Wartendes und Stimmen mit,
     * lässt aber die veröffentlichte Idee stehen — ohne Verfasserbezug.
     */
    public function testKontoloeschungLaesstVeroeffentlichtesStehen(): void
    {
        $client = static::createClient();
        $nutzer = $this->user($client, 'user@endlech.lu');
        $admin = $this->user($client, 'admin@endlech.lu');

        $oeffentlich = $this->idee($client, published: true, von: $nutzer, titel: 'Bleibt stehen');
        $wartend = $this->idee($client, published: false, von: $nutzer, titel: 'Verschwindet');

        $fremde = $this->idee($client, published: true, von: $admin, titel: 'Fremde Idee');
        $client->getContainer()->get(BoardVoteService::class)->toggle($fremde, $nutzer);

        $repo = $client->getContainer()->get(BoardIdeaRepository::class);
        self::assertSame(1, $repo->countVotesForOne($fremde));

        $oeffentlichId = $oeffentlich->getId();
        $wartendId = $wartend->getId();

        $client->getContainer()->get(AccountDeleter::class)->delete($nutzer);

        // ⚠ Ohne clear() antwortet die Identity Map mit den Objekten von vorhin
        // — der Test wäre grün, obwohl nichts gelöscht wurde.
        $client->getContainer()->get(EntityManagerInterface::class)->clear();

        self::assertNotNull($repo->find($oeffentlichId), 'Die veröffentlichte Idee bleibt (AK-65).');
        self::assertNull($repo->find($wartendId), 'Die wartende verschwindet (EC-09).');
        self::assertNull($repo->find($oeffentlichId)->getSubmittedBy(), 'Der Verfasserbezug ist gekappt.');
        self::assertSame(0, $repo->countVotesForOne($fremde), 'Die Stimme verschwindet, die Zahl sinkt (AK-66).');
        self::assertSame(0, $client->getContainer()->get(BoardVoteRepository::class)->count([]));
    }

    /** AK-67: Der Datenexport führt eigene Ideen und Zustimmungen. */
    public function testExportEnthaeltIdeenUndZustimmungen(): void
    {
        $client = static::createClient();
        $nutzer = $this->user($client, 'user@endlech.lu');
        $admin = $this->user($client, 'admin@endlech.lu');

        $this->idee($client, published: true, von: $nutzer, titel: 'Meine Idee');
        $fremde = $this->idee($client, published: true, von: $admin, titel: 'Fremde Idee');
        $client->getContainer()->get(BoardVoteService::class)->toggle($fremde, $nutzer);

        $export = $client->getContainer()->get(AccountDataExporter::class)->export($nutzer);

        self::assertArrayHasKey('boardIdeas', $export);
        self::assertArrayHasKey('boardVotes', $export);
        self::assertSame('Meine Idee', $export['boardIdeas'][0]['title']);
        self::assertSame('Fremde Idee', $export['boardVotes'][0]['ideaTitle']);
    }
}
