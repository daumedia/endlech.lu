<?php

namespace App\Tests\Functional\Controller;

use App\Entity\RestaurantSuggestion;
use App\Repository\RestaurantRepository;
use App\Repository\RestaurantSuggestionRepository;
use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class AdminSuggestionControllerTest extends AbstractWebTestCase
{
    private function createSuggestion(object $client, string $name): RestaurantSuggestion
    {
        $user = $client->getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
        $suggestion = (new RestaurantSuggestion())
            ->setName($name)
            ->setCity('Wiltz')
            ->setCuisine('Italienisch, Pizza')
            ->setSuggestedBy($user);

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->persist($suggestion);
        $em->flush();

        return $suggestion;
    }

    private function suggestions(object $client): RestaurantSuggestionRepository
    {
        return $client->getContainer()->get(RestaurantSuggestionRepository::class);
    }

    public function testIndexForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');
        $client->request('GET', self::LOCALE.'/admin/vorschlaege');

        self::assertResponseStatusCodeSame(403);
    }

    public function testApproveCreatesRestaurantAndMarksApproved(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $name = 'Vorschlag Genehmigt '.uniqid();
        $suggestion = $this->createSuggestion($client, $name);
        $id = $suggestion->getId();

        $crawler = $client->request('GET', self::LOCALE.'/admin/vorschlaege/'.$id);
        $client->submit($this->formByAction($crawler, '/'.$id.'/genehmigen'));

        self::assertResponseRedirects(self::LOCALE.'/admin/vorschlaege');

        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        $restaurant = $client->getContainer()->get(RestaurantRepository::class)->findOneBy(['name' => $name]);
        self::assertNotNull($restaurant, 'Aus dem Vorschlag muss ein Restaurant entstehen.');
        self::assertGreaterThanOrEqual(1, $restaurant->getCuisines()->count());

        self::assertSame(RestaurantSuggestion::STATUS_APPROVED, $this->suggestions($client)->find($id)->getStatus());
    }

    public function testRejectMarksRejectedWithNote(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $suggestion = $this->createSuggestion($client, 'Vorschlag Abgelehnt '.uniqid());
        $id = $suggestion->getId();

        $crawler = $client->request('GET', self::LOCALE.'/admin/vorschlaege/'.$id);
        $client->submit($this->formByAction($crawler, '/'.$id.'/ablehnen', [
            'admin_note' => 'Leider nicht barrierefrei.',
        ]));

        self::assertResponseRedirects(self::LOCALE.'/admin/vorschlaege');

        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        $reloaded = $this->suggestions($client)->find($id);
        self::assertSame(RestaurantSuggestion::STATUS_REJECTED, $reloaded->getStatus());
        self::assertSame('Leider nicht barrierefrei.', $reloaded->getAdminNote());
    }

    public function testApproveWithInvalidCsrfKeepsPending(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $suggestion = $this->createSuggestion($client, 'Vorschlag CSRF '.uniqid());
        $id = $suggestion->getId();

        $client->request('POST', self::LOCALE.'/admin/vorschlaege/'.$id.'/genehmigen', ['_token' => 'ungueltig']);

        self::assertResponseRedirects(self::LOCALE.'/admin/vorschlaege');
        $client->getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertSame(RestaurantSuggestion::STATUS_PENDING, $this->suggestions($client)->find($id)->getStatus());
    }
}
