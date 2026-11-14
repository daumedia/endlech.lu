<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use App\Saved\SavedText;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gemeinsame Daten-Helfer für die Prüfläufe des Bereichs „Gespeichert“ (Feature 17, T17).
 *
 * ⚠ **Daten vor der ersten Anfrage anlegen.** Der Testclient bootet den Kernel vor jeder
 * weiteren Anfrage neu; ein vorher geladenes Konto ist im neuen EntityManager unbekannt und
 * landete beim Persistieren als „neue Entity über eine Beziehung“. Gezählt wird deshalb per SQL.
 */
trait SavedPagesFixtures
{
    private const string STREAM = 'text/vnd.turbo-stream.html, text/html, application/xhtml+xml';

    private function deckelZuruecksetzen(): void
    {
        $client = static::createClient();
        foreach (['user@endlech.lu', 'admin@endlech.lu', 'unverified@endlech.lu'] as $email) {
            $client->getContainer()->get('limiter.saved_change')->create('konto-'.$this->user($client, $email)->getId())->reset();
        }
        self::ensureKernelShutdown();
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Neue Lokale in Anlegereihenfolge.
     *
     * @return list<Restaurant>
     */
    private function neueLokale(KernelBrowser $client, int $anzahl, string $praefix = 'Testlokal'): array
    {
        $lokale = [];
        for ($i = 1; $i <= $anzahl; ++$i) {
            $lokal = (new Restaurant())->setName(sprintf('%s %02d', $praefix, $i))->setCity('Luxembourg');
            $this->em($client)->persist($lokal);
            $lokale[] = $lokal;
        }
        $this->em($client)->flush();

        return $lokale;
    }

    private function liste(KernelBrowser $client, User $konto, string $name): SavedList
    {
        $liste = new SavedList($konto, SavedText::name($name), SavedText::nameKey($name));
        $this->em($client)->persist($liste);
        $this->em($client)->flush();

        return $liste;
    }

    /**
     * @param list<Restaurant> $lokale in Anlegereihenfolge — angezeigt wird umgekehrt
     */
    private function eintraege(KernelBrowser $client, SavedList $liste, array $lokale): void
    {
        foreach ($lokale as $lokal) {
            $this->em($client)->persist(new SavedListEntry($liste, $lokal));
            // Eigener flush je Eintrag: Bei gleichem Zeitstempel ordnet die Nummer (AK-25).
            $this->em($client)->flush();
        }
    }

    /**
     * @param list<Restaurant> $lokale
     */
    private function favoriten(KernelBrowser $client, User $konto, array $lokale): void
    {
        foreach ($lokale as $lokal) {
            $this->em($client)->persist(new Favorite($konto, $lokal));
            $this->em($client)->flush();
        }
    }

    private function notiz(KernelBrowser $client, User $konto, Restaurant $lokal, string $text): void
    {
        $this->em($client)->persist(new RestaurantNote($konto, $lokal, $text));
        $this->em($client)->flush();
    }

    private function sql(KernelBrowser $client, string $abfrage, array $parameter = []): int
    {
        return (int) $this->em($client)->getConnection()->fetchOne($abfrage, $parameter);
    }

    private function listenZahl(KernelBrowser $client, User $konto): int
    {
        return $this->sql($client, 'SELECT COUNT(*) FROM saved_list WHERE user_id = ?', [$konto->getId()]);
    }

    private function eintragsZahl(KernelBrowser $client, int $listId): int
    {
        return $this->sql($client, 'SELECT COUNT(*) FROM saved_list_entry WHERE list_id = ?', [$listId]);
    }

    private function listenName(KernelBrowser $client, int $listId): ?string
    {
        $name = $this->em($client)->getConnection()->fetchOne('SELECT name FROM saved_list WHERE id = ?', [$listId]);

        return false === $name ? null : (string) $name;
    }

    private function deckelRest(KernelBrowser $client, User $konto): int
    {
        return $client->getContainer()->get('limiter.saved_change')->create('konto-'.$konto->getId())->consume(0)->getRemainingTokens();
    }

    /**
     * Der Notiztext unter der Karte eines Lokals (T21: die Karte der Suche, `article#karte-{id}`).
     * Leer, wenn die Karte keine Notiz trägt oder es die Karte nicht gibt.
     */
    private function notizInKarte(Crawler $seite, ?int $lokalId): Crawler
    {
        return $seite->filter('article#karte-'.$lokalId)
            ->filterXPath('descendant::p[normalize-space(.)="Deine Notiz"]/following-sibling::p[1]');
    }

    /** Das Kartenraster aus einer Stream-Antwort (replace mit morph auf `gespeichert-karten`). */
    private function raster(string $inhalt): Crawler
    {
        self::assertSame(1, preg_match('#<turbo-stream action="replace" method="morph" target="gespeichert-karten">\s*<template>(.*?)</template>\s*</turbo-stream>#s', $inhalt, $treffer), 'Stream ersetzt das Kartenraster.');

        return new Crawler('<!DOCTYPE html><html><body>'.$treffer[1].'</body></html>');
    }

    /**
     * Ein **gültiges** Sitzungstoken des angemeldeten Kontos für eine beliebige Kennung —
     * so, wie es eine Seite des Kontos ausgäbe. Nötig für AK-54: Konto B soll mit einem echten
     * eigenen Token an der Liste von A scheitern, nicht schon am Token.
     */
    private function sitzungsToken(KernelBrowser $client, string $kennung): string
    {
        $sitzung = $client->getSession();
        self::assertNotNull($sitzung);
        $anfrage = new Request();
        $anfrage->setSession($sitzung);
        $stapel = $client->getContainer()->get('request_stack');
        $stapel->push($anfrage);
        try {
            $wert = $client->getContainer()->get('security.csrf.token_manager')->getToken($kennung)->getValue();
        } finally {
            $stapel->pop();
        }
        $sitzung->save();

        return $wert;
    }
}
