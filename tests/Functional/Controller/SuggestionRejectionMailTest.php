<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\RestaurantSuggestion;
use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * BF-55: Die Ablehnungsnotiz erreicht den Einreicher.
 *
 * Sie wurde gespeichert und ging nirgendwohin — keine Route, keine Mail, kein
 * Platz im Profil. Zusammen mit dem fehlenden Rückkanal in B11 sah der
 * Einreicher seinen Vorschlag zu keinem Zeitpunkt wieder.
 */
final class SuggestionRejectionMailTest extends AbstractWebTestCase
{
    private function vorschlag(EntityManagerInterface $em, ?User $einreicher, string $locale = 'de'): RestaurantSuggestion
    {
        $suggestion = new RestaurantSuggestion();
        $suggestion->setName('Ablehnung '.uniqid());
        $suggestion->setCity('Wiltz');
        $suggestion->setCuisine('Test');
        $suggestion->setLocale($locale);
        $suggestion->setSuggestedBy($einreicher);
        $em->persist($suggestion);
        $em->flush();

        return $suggestion;
    }

    private function ablehnen(object $client, RestaurantSuggestion $suggestion, string $notiz = ''): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/admin/vorschlaege/'.$suggestion->getId());
        $formular = $this->formByAction($crawler, '/ablehnen');

        $werte = $formular->getPhpValues();
        if ('' !== $notiz) {
            $werte['admin_note'] = $notiz;
        }

        $client->request('POST', $formular->getUri(), $werte, [], [
            'HTTP_REFERER' => 'http://localhost'.self::LOCALE.'/admin/vorschlaege/'.$suggestion->getId(),
        ]);
    }

    public function testAblehnungSchicktEineNachrichtMitDerNotiz(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $einreicher = $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);
        $suggestion = $this->vorschlag($em, $einreicher);

        $this->ablehnen($client, $suggestion, 'Die Adresse gibt es nicht.');

        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertNotNull($mail);
        self::assertSame('user@endlech.lu', $mail->getTo()[0]->getAddress());
        self::assertStringContainsString('Die Adresse gibt es nicht.', $mail->getHtmlBody());
    }

    /**
     * Die Sprache folgt dem Vorschlag, nicht dem Admin — der Einreicher hat den
     * Assistenten in seiner Sprache ausgefüllt.
     */
    #[DataProvider('sprachen')]
    public function testNachrichtFolgtDerSpracheDerEinreichung(string $locale, string $erwartet): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $einreicher = $em->getRepository(User::class)->findOneBy(['email' => 'user@endlech.lu']);

        $this->ablehnen($client, $this->vorschlag($em, $einreicher, $locale));

        self::assertEmailCount(1);
        self::assertStringContainsString($erwartet, (string) self::getMailerMessage()?->getHtmlBody());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function sprachen(): iterable
    {
        yield 'deutsch' => ['de', 'Danke'];
        yield 'französisch' => ['fr', 'Merci'];
        yield 'englisch' => ['en', 'Thank you'];
    }

    /**
     * Ein Vorschlag ohne Konto kann keine Nachricht bekommen — und darf deswegen
     * auch nicht scheitern (Altbestand vor der Anmeldepflicht).
     */
    public function testVorschlagOhneEinreicherLaeuftDurch(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $suggestion = $this->vorschlag($em, null);

        $this->ablehnen($client, $suggestion);

        self::assertEmailCount(0);
        self::assertResponseRedirects();
    }
}
