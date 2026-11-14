<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * QA B04 — Nachprüfung des Fehlerauftrags BF-182, BF-184, BF-186 (2026-11-14).
 *
 * Reproduktionen der in diesem Durchlauf gefundenen Befunde. Sie prüfen das richtige Verhalten, waren beim Schreiben
 * rot und überspringen sich, solange der Befund offen ist. Scharf:
 * `QAB04_REPRO=1 php bin/phpunit tests/Functional/Controller/QaB04NachpruefungTest.php`.
 * Wer den Befund behebt, entfernt den Aufruf `$this->offen(…)` — und ändert sonst nichts an der Prüfung.
 */
final class QaB04NachpruefungTest extends AbstractWebTestCase
{
    private const string AVATARE = __DIR__.'/../../../public/uploads/avatars';

    /** @var list<string> */
    private array $aufzuraeumen = [];

    protected function tearDown(): void
    {
        foreach ($this->aufzuraeumen as $pfad) {
            @unlink($pfad);
        }
        parent::tearDown();
    }

    private function offen(string $befund): void
    {
        if ('1' !== getenv('QAB04_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/B04-profil-avatar-einreichungen/qa-report.md. Scharf mit QAB04_REPRO=1.');
        }
    }

    private function client(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        return $client;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    private function konto(KernelBrowser $client, string $email, string $name = 'Qa B04 Konto'): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword('unbenutzt');
        $user->setRoles([]);
        $user->setIsVerified(true);

        $this->em($client)->persist($user);
        $this->em($client)->flush();

        return $user;
    }

    private function png(): string
    {
        $pfad = sys_get_temp_dir().'/qab04-'.bin2hex(random_bytes(4)).'.png';
        imagepng(imagecreatetruecolor(4, 4), $pfad);
        $this->aufzuraeumen[] = $pfad;

        return $pfad;
    }

    /**
     * Neuer Befund (BF-226, vorbestehend in B04, als OF-06 aus dem Bau gemeldet) — nach einer Meldung am Profilformular
     * trägt das neu gerenderte Formular kein `action`. Turbo lässt nach der 422-Antwort die Adresse `/xx/profile` stehen;
     * die Korrektur geht an `POST /xx/profile` (nur GET) → 405, die Eingabe ist verloren. Im Browser gemessen auf vier
     * Wegen (Name zu kurz, Name aus Leerzeichen, RFC-widrige Adresse, zu großes Bild). Muster BF-151.
     */
    public function testBf226ProfilformularBehaeltNachEinerMeldungSeinZiel(): void
    {
        $this->offen('BF-226');
        $client = $this->client();
        $client->loginUser($this->konto($client, 'qab04-ziel@example.org'));

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($crawler->filter('form[name="profile"]')->form(['profile[name]' => 'a']));
        self::assertResponseStatusCodeSame(422);

        self::assertSame(
            self::LOCALE.'/profile/edit',
            $client->getCrawler()->filter('form[name="profile"]')->attr('action'),
            'Das Formular auf der 422-Seite muss wieder an die POST-Route gehen — die Adresse im Browser ist /profile.',
        );
    }

    /**
     * BF-226, zweite Stelle: dasselbe am Passwortformular (`changePassword()` baut `ChangePasswordType` ohne `action`).
     * Im Browser gemessen: neues Passwort zu kurz → Meldung → Korrektur an `POST /de/profile` → 405.
     */
    public function testBf226PasswortformularBehaeltNachEinerMeldungSeinZiel(): void
    {
        $this->offen('BF-226');
        $client = $this->client();
        $client->loginUser($this->konto($client, 'qab04-ziel-pw@example.org'));

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($crawler->filter('form[name="change_password"]')->form([
            'change_password[currentPassword]' => 'egal',
            'change_password[newPassword][first]' => 'kurz',
            'change_password[newPassword][second]' => 'kurz',
        ]));
        self::assertResponseStatusCodeSame(422);

        self::assertSame(
            self::LOCALE.'/profile/password',
            $client->getCrawler()->filter('form[name="change_password"]')->attr('action'),
        );
    }

    /**
     * Gegenprobe ohne Überspringen: Die Route `/profile` nimmt kein POST an. Genau dorthin schickt der Browser das
     * Formular ohne `action`, sobald Turbo die Adresse stehen lässt — das ist die 405 aus BF-226.
     */
    public function testGegenprobeProfilseiteNimmtKeinPostAn(): void
    {
        $client = $this->client();
        $client->loginUser($this->konto($client, 'qab04-post@example.org'));

        $client->request('POST', self::LOCALE.'/profile', ['profile' => ['name' => 'Qa Korrigiert']]);

        self::assertResponseStatusCodeSame(405);
    }

    /**
     * Neuer Befund (BF-228, vorbestehend in B04; Hinweis des code-reviewer, am Server nachgestellt) — beim erreichten
     * Deckel `email_change` hängt eine Namensänderung im selben Absenden davon ab, ob ein Bild mitkommt: ohne Bild ist
     * sie verworfen, mit Bild gespeichert (der `flush()` des Upload-Dienstes nimmt sie mit). Die Meldung nennt in beiden
     * Fällen nur die Adresse.
     *
     * Geprüft wird die Gleichheit, nicht eine der beiden Richtungen: Beide Absendungen müssen beim Namen dasselbe Ergebnis
     * haben.
     */
    public function testBf228NamensaenderungBeimErreichtenDeckelHaengtNichtAmBild(): void
    {
        $this->offen('BF-228');
        $client = $this->client();
        $fabrik = $client->getContainer()->get('limiter.email_change');
        $ergebnis = [];

        foreach (['ohne' => false, 'mit' => true] as $fall => $mitBild) {
            $adresse = 'qab04-deckel-'.$fall.'@example.org';
            $user = $this->konto($client, $adresse, 'Qa Alter Name');
            $fabrik->create($adresse)->consume(10000);
            $client->loginUser($user);

            $crawler = $client->request('GET', self::LOCALE.'/profile');
            $form = $crawler->filter('form[name="profile"]')->form([
                'profile[name]' => 'Qa Neuer Name',
                'profile[email]' => 'qab04-deckel-'.$fall.'-neu@example.org',
            ]);
            if ($mitBild) {
                $form['profile[avatar]']->upload($this->png());
            }
            $client->submit($form);
            self::assertResponseRedirects(self::LOCALE.'/profile');

            $zeile = $this->em($client)->getConnection()->fetchAssociative('SELECT name, avatar_filename FROM `user` WHERE id = ?', [$user->getId()]);
            if (\is_string($zeile['avatar_filename'] ?? null)) {
                $this->aufzuraeumen[] = self::AVATARE.'/'.$zeile['avatar_filename'];
            }
            $ergebnis[$fall] = $zeile['name'] ?? null;
        }

        self::assertSame(
            'Qa Neuer Name' === $ergebnis['ohne'],
            'Qa Neuer Name' === $ergebnis['mit'],
            'Dieselbe Namensänderung wird ohne Bild verworfen und mit Bild gespeichert: '.json_encode($ergebnis),
        );
    }
}
