<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Basisklasse für funktionale Web-Tests.
 *
 * Web-Routen tragen den Locale-Prefix (config/routes.yaml) – daher self::LOCALE
 * vor jeden Pfad setzen. DAMADoctrineTestBundle isoliert jeden Test per Transaktion.
 */
abstract class AbstractWebTestCase extends WebTestCase
{
    protected const LOCALE = '/de';

    protected function user(KernelBrowser $client, string $email): User
    {
        $user = $client->getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, "Fixture-User {$email} fehlt – Test-DB nicht geladen?");

        return $user;
    }

    protected function loginAs(KernelBrowser $client, string $email): User
    {
        $user = $this->user($client, $email);
        $client->loginUser($user);

        return $user;
    }

    /**
     * Liest den (session-basierten) CSRF-Token-Wert eines Hidden-Felds oder
     * data-Attributs aus der zuletzt gerenderten Seite – nötig für die Custom-Token-IDs
     * (toggle-verified-…, sort-images-… usw.), die NICHT stateless sind.
     */
    protected function csrfTokenFrom(Crawler $crawler, string $cssSelector, string $attribute = 'value'): string
    {
        $node = $crawler->filter($cssSelector);
        self::assertGreaterThan(0, $node->count(), "CSRF-Token-Knoten nicht gefunden: {$cssSelector}");

        return (string) $node->attr($attribute);
    }

    /**
     * Liefert das erste Formular der Seite, das ein Feld mit dem gegebenen name-Attribut
     * enthält (robust gegenüber zusätzlichen Formularen wie Logout/Nav). Das gerenderte
     * _token wird vom Form-Objekt automatisch übernommen.
     *
     * @param array<string, string> $values
     */
    protected function formWithField(Crawler $crawler, string $fieldName, array $values = []): Form
    {
        $forms = $crawler->filter('form');
        for ($i = 0; $i < $forms->count(); ++$i) {
            $form = $forms->eq($i);
            if ($form->filter('[name="'.$fieldName.'"]')->count() > 0) {
                return $form->form($values);
            }
        }

        self::fail("Kein Formular mit Feld {$fieldName} gefunden.");
    }

    /**
     * @param array<string, string> $values
     */
    protected function formByAction(Crawler $crawler, string $actionSuffix, array $values = []): Form
    {
        $node = $crawler->filter('form[action$="'.$actionSuffix.'"]');
        self::assertGreaterThan(0, $node->count(), "Kein Formular mit action {$actionSuffix}.");

        return $node->first()->form($values);
    }

    /**
     * Die Antwort ohne die Quelltext-Auszüge der Debug-Fehlerseite.
     *
     * Seit Symfony 8.1 zitiert die Fehlerseite im Debug-Modus — also in jedem Test — den
     * Quelltext jedes Stack-Frames, auch den des Tests selbst. Damit steht die Zeichenkette,
     * auf die ein „verrät die 404-Seite nichts?"-Test prüft, in jeder 404-Antwort: als Zitat
     * der Testzeile. Entfernt werden nur diese Auszüge (`div.trace-code`); alles, was die
     * Anwendung selbst ausgibt, bleibt drin und wird weiter geprüft.
     */
    protected static function antwortOhneQuelltextAuszug(KernelBrowser $client): string
    {
        return (string) preg_replace(
            '#<div id="trace-html-[^"]*" class="trace-code[^"]*">.*?</div>#s',
            '',
            (string) $client->getResponse()->getContent(),
        );
    }
}
