<?php

namespace App\Tests\Functional\Controller;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Repository\OrganisationWaitlistEntryRepository;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class OrganisationControllerTest extends AbstractWebTestCase
{
    /** @return array<string, string|bool> */
    private static function base(string $type): array
    {
        return [
            'organisation_waitlist[type]' => $type,
            'organisation_waitlist[organisationName]' => 'Testorganisation',
            'organisation_waitlist[contactName]' => 'Alex Muster',
            'organisation_waitlist[email]' => 'alex@example.lu',
            'organisation_waitlist[consent]' => true,
        ];
    }

    public function testLandingPageRendersAllThreeSections(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('h1')->count(), 'Die Seite muss genau eine h1 haben.');

        // Die ausführlichen Inhalte liegen auf den Unterseiten; die Übersicht
        // führt hin und trägt das Formular.
        self::assertGreaterThan(0, $crawler->filter('#anfrage')->count());

        // Der Typ-Selektor muss eine echte Radiogruppe sein, keine klickbaren Divs.
        self::assertSame(3, $crawler->filter('input[type="radio"][name="organisation_waitlist[type]"]')->count());

        // Ohne JavaScript müssen alle typspezifischen Blöcke vorhanden sein.
        self::assertSame(3, $crawler->filter('[data-organisation-type-target="block"]')->count());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function typePages(): array
    {
        return [
            'Gemeinden' => ['gemeinden', 'commune'],
            'Unternehmen' => ['unternehmen', 'company'],
            'Vereine' => ['vereine', 'association'],
        ];
    }

    #[DataProvider('typePages')]
    public function testTypePageRendersWithPreselectedType(string $slug, string $expectedType): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen/' . $slug);

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('h1')->count(), 'Jede Unterseite braucht genau eine h1.');

        // Der Typ ist vorgewählt, damit das Formular direkt passt.
        self::assertNotNull(
            $crawler->filter('input[value="' . $expectedType . '"]')->attr('checked'),
            "Typ {$expectedType} muss auf /{$slug} vorgewählt sein.",
        );

        // Das Formular ist auf jeder Unterseite erreichbar.
        self::assertGreaterThan(0, $crawler->filter('#anfrage')->count());
    }

    public function testUnknownTypeSlugReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/organisationen/gibtesnicht');

        self::assertResponseStatusCodeSame(404);
    }

    public function testOverviewLinksToAllTypePages(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        foreach (['gemeinden', 'unternehmen', 'vereine'] as $slug) {
            self::assertGreaterThan(
                0,
                $crawler->filter('a[href="' . self::LOCALE . '/organisationen/' . $slug . '"]')->count(),
                "Die Übersicht muss auf /{$slug} verlinken.",
            );
        }
    }

    public function testTypeCanBePreselectedViaQuery(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen?type=association');

        self::assertResponseIsSuccessful();
        self::assertNotNull(
            $crawler->filter('input[value="association"]')->attr('checked'),
            'Der Typ aus ?type= muss vorausgewählt sein.',
        );
    }

    public function testCommuneSubmissionStoresTypeSpecificFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        $values = self::base('commune') + [
            'organisation_waitlist[communeName]' => 'Strassen',
            'organisation_waitlist[estimatedVenues]' => '42',
            'organisation_waitlist[timeframe]' => 'next_budget_year',
        ];

        $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', $values));

        self::assertResponseRedirects(self::LOCALE . '/organisationen');

        $entry = $this->latest($client);
        self::assertSame(OrganisationType::COMMUNE, $entry?->getType());
        self::assertSame('Strassen', $entry->getCommuneName());
        self::assertSame(42, $entry->getEstimatedVenues());
        self::assertSame(WaitlistStatus::PENDING, $entry->getStatus());
        self::assertSame([], $entry->getSponsorshipInterests(), 'Eine Gemeinde darf keine Sponsoring-Interessen haben.');
        self::assertEmailCount(1);
    }

    public function testCompanySubmissionStoresSponsorshipInterests(): void
    {
        $client = static::createClient();
        $this->rawSubmit($client, self::base('company'), [
            'sponsorshipInterests' => ['inclusion_boxes', 'workshops'],
        ]);

        self::assertResponseRedirects(self::LOCALE . '/organisationen');

        $entry = $this->latest($client);
        self::assertSame(OrganisationType::COMPANY, $entry?->getType());
        self::assertSame(['inclusion_boxes', 'workshops'], $entry->getSponsorshipInterests());
        self::assertNull($entry->getEstimatedVenues(), 'Ein Unternehmen darf keine Lokal-Zahl haben.');
    }

    public function testAssociationSubmissionStoresCollaborationInterests(): void
    {
        $client = static::createClient();
        $this->rawSubmit($client, self::base('association'), [
            'collaborationInterests' => ['advisory_board'],
        ]);

        $entry = $this->latest($client);
        self::assertSame(OrganisationType::ASSOCIATION, $entry?->getType());
        self::assertSame(['advisory_board'], $entry->getCollaborationInterests());
    }

    /**
     * Kern der bedingten Validierung: Ein manipulierter Request, der einer
     * Gemeinde Sponsoring-Interessen unterschiebt, wird abgelehnt – nicht still
     * ignoriert. PRE_SUBMIT baut das Feld für diesen Typ gar nicht auf, Symfony
     * behandelt es damit als unerlaubtes Zusatzfeld.
     */
    public function testCrossTypeFieldsAreRejected(): void
    {
        $client = static::createClient();
        $this->rawSubmit($client, self::base('commune'), [
            'sponsorshipInterests' => ['inclusion_boxes'],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->latest($client), 'Ein typfremdes Feld darf keinen Eintrag erzeugen.');
    }

    /**
     * Die Gegenprobe: Dasselbe Feld ist für den passenden Typ zulässig.
     */
    public function testOwnTypeFieldsAreAccepted(): void
    {
        $client = static::createClient();
        $this->rawSubmit($client, self::base('commune'), ['estimatedVenues' => '7']);

        self::assertResponseRedirects(self::LOCALE . '/organisationen');
        self::assertSame(7, $this->latest($client)?->getEstimatedVenues());
    }

    public function testMissingTypeIsRejected(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        $values = self::base('commune');
        unset($values['organisation_waitlist[type]']);

        $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', $values));

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->latest($client));
    }

    public function testInvalidSubmissionFocusesFirstError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        $values = self::base('commune');
        $values['organisation_waitlist[organisationName]'] = '';

        $crawler = $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', $values));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(1, $crawler->filter('#organisation_waitlist_organisationName[autofocus]')->count());
        self::assertSame('true', $crawler->filter('#organisation_waitlist_organisationName')->attr('aria-invalid'));
    }

    public function testHoneypotIsSilentlyDiscarded(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        $values = self::base('commune') + ['organisation_waitlist[companyWebsite]' => 'https://spam.example'];
        $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', $values));

        self::assertResponseRedirects(self::LOCALE . '/organisationen');
        self::assertNull($this->latest($client));
        self::assertEmailCount(0);
    }

    public function testTurboRequestReturnsStream(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        $client->submit(
            $this->formWithField($crawler, 'organisation_waitlist[email]', self::base('commune')),
            [],
            ['HTTP_ACCEPT' => 'text/vnd.turbo-stream.html, text/html'],
        );

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            '<turbo-stream action="replace" target="organisation-waitlist-form">',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testConfirmationActivatesEntryAndNotifiesTeam(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');
        $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', self::base('association')));

        $token = (string) $this->latest($client)?->getConfirmationToken();
        $client->request('GET', self::LOCALE . '/organisationen/confirmation/' . $token);

        self::assertResponseIsSuccessful();
        self::assertSame(WaitlistStatus::CONFIRMED, $this->latest($client)?->getStatus());
        // Nur die interne Meldung – der Client bootet je Request neu.
        self::assertEmailCount(1);
    }

    public function testUnknownTokenReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/organisationen/confirmation/' . str_repeat('b', 64));

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Sendet das Formular als Roh-POST. Nötig für die Mehrfachauswahl-Felder
     * (DomCrawler bildet `name[]`-Checkboxen als Array einzelner Felder ab) und
     * um typfremde Felder gezielt unterzuschieben.
     *
     * Der Referer ist Pflicht: Die stateless CSRF-Prüfung akzeptiert den
     * Request nur bei gleicher Herkunft; submit() setzt ihn sonst automatisch.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $extra
     */
    private function rawSubmit(KernelBrowser $client, array $values, array $extra = []): void
    {
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');
        $raw = $this->formWithField($crawler, 'organisation_waitlist[email]', $values)->getPhpValues();

        foreach ($extra as $key => $value) {
            $raw['organisation_waitlist'][$key] = $value;
        }

        $client->request(
            'POST',
            self::LOCALE . '/organisationen',
            $raw,
            [],
            ['HTTP_REFERER' => 'http://localhost' . self::LOCALE . '/organisationen'],
        );
    }

    private function latest(KernelBrowser $client): ?OrganisationWaitlistEntry
    {
        return $client->getContainer()->get(OrganisationWaitlistEntryRepository::class)
            ->findOneBy([], ['id' => 'DESC']);
    }
}
