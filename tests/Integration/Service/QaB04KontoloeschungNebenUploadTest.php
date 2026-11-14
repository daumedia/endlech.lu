<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Account\AccountDeleter;
use App\Entity\User;
use App\Marketing\MarketingContactRegistry;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\BoardIdeaRepository;
use App\Repository\UserRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Lock\LockFactory;

/**
 * QA B04 — Nachprüfung 2026-11-14. Neuer Befund BF-227 (als OF-07 aus dem Bau gemeldet, am Server nachgestellt).
 *
 * Die Kontolöschung räumt das Bild über `AvatarUploadService::delete()` ab und gibt danach die Sperre aus BF-173 frei —
 * bevor die Zeile `user` verschwindet. Ein Upload desselben Kontos aus einer zweiten Sitzung (anderes Gerät; zwei Tabs
 * eines Browsers teilen die Sitzung) bekommt die Sperre in diesem Moment oder danach, legt seine Datei ab und schreibt
 * den Namen an eine Zeile, die gleich gelöscht wird oder schon gelöscht ist (`UPDATE` trifft dann nichts, kein Fehler).
 * Die Datei bleibt öffentlich unter `/uploads/avatars/` liegen, zu einem Konto, dessen Löschung verlangt wurde.
 *
 * Am Server: 31 Durchgänge mit zwei Sitzungen, fünf verwaiste Bilder (`qa/B04/QA-OF07-kontoloeschung-neben-upload.ausgabe.txt`).
 *
 * Nachgebildet wird die zweite Sitzung mit dem ECHTEN `upload()` (samt Sperre) und einer eigenen Instanz des Kontos, wie
 * sie die andere Anfrage beim Anfragebeginn geladen hat. Zwei Zeitpunkte: zwischen Bildentfernung und Zeilenlöschung —
 * nur, wenn die Sperre dort frei ist; hält eine Reparatur sie bis zur Zeilenlöschung, wartete die andere Anfrage und
 * kommt danach dran — und nach der Löschung.
 *
 * Scharf: `QAB04_REPRO=1 php bin/phpunit tests/Integration/Service/QaB04KontoloeschungNebenUploadTest.php`.
 * Wer den Befund behebt, entfernt den Aufruf `$this->offen(…)` — und ändert sonst nichts an der Prüfung.
 */
final class QaB04KontoloeschungNebenUploadTest extends KernelTestCase
{
    private string $verzeichnis;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->verzeichnis = sys_get_temp_dir().'/endlech_qab04_'.uniqid();
        mkdir($this->verzeichnis, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->verzeichnis.'/*') ?: [] as $datei) {
            @unlink($datei);
        }
        @rmdir($this->verzeichnis);
        parent::tearDown();
    }

    private function offen(string $befund): void
    {
        if ('1' !== getenv('QAB04_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/B04-profil-avatar-einreichungen/qa-report.md. Scharf mit QAB04_REPRO=1.');
        }
    }

    public function testBf227UploadZwischenBildentfernungUndZeilenloeschungHinterlaesstKeineDatei(): void
    {
        $this->offen('BF-227');
        $this->pruefe(zwischendurch: true);
    }

    public function testBf227UploadNachDerKontoloeschungHinterlaesstKeineDatei(): void
    {
        $this->offen('BF-227');
        $this->pruefe(zwischendurch: false);
    }

    private function pruefe(bool $zwischendurch): void
    {
        $user = new User();
        $user->setName('QaB04 Konto')->setEmail('qab04-bf227@example.org')->setPassword('unbenutzt')->setIsVerified(true);
        $user->setRoles([]);
        $this->em->persist($user);
        $this->em->flush();
        $id = (int) $user->getId();

        // Das Konto, wie es die andere Anfrage beim Anfragebeginn geladen hat: eigene Instanz, kein Bild.
        $andereAnfrage = new User();
        $andereAnfrage->setName('QaB04 Konto')->setEmail('qab04-bf227@example.org')->setPassword('unbenutzt');
        (new \ReflectionProperty(User::class, 'id'))->setValue($andereAnfrage, $id);

        $locks = static::getContainer()->get(LockFactory::class);
        $hochladen = function () use ($andereAnfrage): void {
            $quelle = $this->verzeichnis.'/quelle-'.bin2hex(random_bytes(3)).'.png';
            imagepng(imagecreatetruecolor(4, 4), $quelle);
            $this->dienst()->upload(new UploadedFile($quelle, 'foto.png', 'image/png', null, true), $andereAnfrage);
            // Was der `flush()` der anderen Anfrage schreibt (ihre Entity ist dort verwaltet, hier nicht). Ist die Zeile
            // schon weg, trifft das `UPDATE` nichts — wie in der Anwendung.
            $this->em->getConnection()->executeStatement('UPDATE `user` SET avatar_filename = ? WHERE id = ?', [$andereAnfrage->getAvatarFilename(), $andereAnfrage->getId()]);
        };

        $nachher = new \ArrayObject();
        $dienst = new class($this->verzeichnis, $this->em, $locks, $zwischendurch, $hochladen, $nachher) extends AvatarUploadService {
            public function __construct(
                string $dir,
                EntityManagerInterface $em,
                private readonly LockFactory $sperren,
                private readonly bool $zwischendurch,
                private readonly \Closure $hochladen,
                private readonly \ArrayObject $nachher,
            ) {
                parent::__construct($dir, $em, $sperren);
            }

            public function delete(User $user): void
            {
                parent::delete($user);
                if (!$this->zwischendurch) {
                    return;
                }
                // Die andere Anfrage bekommt die Sperre jetzt — wenn sie frei ist. Sonst wartet sie bis danach.
                $probe = $this->sperren->createLock('avatar-upload-'.$user->getId());
                if ($probe->acquire(false)) {
                    $probe->release();
                    ($this->hochladen)();
                } else {
                    $this->nachher[] = $this->hochladen;
                }
            }
        };

        $c = static::getContainer();
        (new AccountDeleter(
            $this->em,
            $c->get(UserRepository::class),
            $dienst,
            $c->get(MarketingContactRegistry::class),
            $c->get(BoardIdeaRepository::class),
            $c->get(AppWaitlistEntryRepository::class),
        ))->delete($user);

        if (!$zwischendurch) {
            $hochladen();
        }
        foreach ($nachher as $spaeter) {
            $spaeter();
        }

        clearstatcache();
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM `user` WHERE id = ?', [$id]));
        $bilder = array_values(array_filter(
            glob($this->verzeichnis.'/*') ?: [],
            static fn (string $pfad): bool => !str_starts_with(basename($pfad), 'quelle-'),
        ));
        self::assertSame([], $bilder, 'Nach der Kontolöschung darf keine Bilddatei des Kontos liegen bleiben (Art. 17 DSGVO, BF-53).');
    }

    private function dienst(): AvatarUploadService
    {
        return new AvatarUploadService($this->verzeichnis, $this->em, static::getContainer()->get(LockFactory::class));
    }
}
