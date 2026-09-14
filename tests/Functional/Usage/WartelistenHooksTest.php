<?php

namespace App\Tests\Functional\Usage;

use App\Tests\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 11 · Wartelisten-Trichter: Nur die Erfolgsmeldung meldet `warteliste_eingetragen`
 * (AK-15, AK-16, AK-17).
 */
final class WartelistenHooksTest extends AbstractWebTestCase
{
    private const array TURBO = ['HTTP_ACCEPT' => 'text/vnd.turbo-stream.html, text/html'];

    private function erfolgsmeldung(KernelBrowser $client): Crawler
    {
        self::assertResponseIsSuccessful();
        $inhalt = (string) $client->getResponse()->getContent();
        // Der Turbo-Stream trägt die Meldung in einem <template>; der Crawler liest dessen Inhalt nicht.
        preg_match('#<template>(.*)</template>#s', $inhalt, $treffer);

        return (new Crawler($treffer[1] ?? ''))->filter('[data-controller="usage-event"]');
    }

    private function pruefeMeldung(Crawler $meldung, string $liste): void
    {
        self::assertCount(1, $meldung);
        self::assertSame('warteliste_eingetragen', $meldung->attr('data-usage-event-name-value'));
        self::assertSame(['liste' => $liste], json_decode((string) $meldung->attr('data-usage-event-data-value'), true));
        self::assertSame('true', $meldung->attr('data-usage-event-on-connect-value'));
        // AK-17: keine Adresse im Ereignis — auch wenn die Meldung selbst sie nennt.
        self::assertStringNotContainsString('@', (string) $meldung->attr('data-usage-event-data-value'));
    }

    public function testAppWarteliste(): void
    {
        $client = static::createClient();
        $formular = $this->formWithField($client->request('GET', self::LOCALE.'/app'), 'app_waitlist[email]');
        $formular['app_waitlist[email]'] = 'messung-app@example.lu';
        $formular['app_waitlist[platform]']->select('ios');
        $formular['app_waitlist[consent]']->tick();

        $client->submit($formular, [], self::TURBO);

        $this->pruefeMeldung($this->erfolgsmeldung($client), 'app');
    }

    public function testPartnerWarteliste(): void
    {
        $client = static::createClient();
        $client->submit($this->formWithField($client->request('GET', self::LOCALE.'/partner'), 'partner_waitlist[email]', [
            'partner_waitlist[restaurantName]' => 'Brasserie Messung',
            'partner_waitlist[contactName]' => 'Anna Muster',
            'partner_waitlist[email]' => 'messung-partner@example.lu',
            'partner_waitlist[locality]' => 'Strassen',
            'partner_waitlist[consent]' => true,
        ]), [], self::TURBO);

        $this->pruefeMeldung($this->erfolgsmeldung($client), 'partner');
    }

    public function testOrganisationsWarteliste(): void
    {
        $client = static::createClient();
        $client->submit($this->formWithField($client->request('GET', self::LOCALE.'/organisationen'), 'organisation_waitlist[email]', [
            'organisation_waitlist[type]' => 'commune',
            'organisation_waitlist[organisationName]' => 'Gemeinde Messung',
            'organisation_waitlist[contactName]' => 'Alex Muster',
            'organisation_waitlist[email]' => 'messung-organisation@example.lu',
            'organisation_waitlist[consent]' => true,
        ]), [], self::TURBO);

        $this->pruefeMeldung($this->erfolgsmeldung($client), 'organisation');
    }

    /** AK-16 · Eine fehlerhafte Absendung (422) enthält den Auslöser nicht. */
    public function testFehlerhafteAbsendungMeldetNichts(): void
    {
        $client = static::createClient();
        $formular = $this->formWithField($client->request('GET', self::LOCALE.'/app'), 'app_waitlist[email]');
        $formular['app_waitlist[email]'] = 'keine-adresse';

        $client->submit($formular, [], self::TURBO);

        self::assertResponseStatusCodeSame(422);
        self::assertStringNotContainsString('warteliste_eingetragen', (string) $client->getResponse()->getContent());
    }

    /** Die Formularseite selbst meldet nichts — der Schritt „Seite geöffnet" ist der Seitenaufruf. */
    public function testFormularseiteMeldetKeinEreignis(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/app');

        self::assertStringNotContainsString('warteliste_eingetragen', (string) $client->getResponse()->getContent());
    }
}
