<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * BF-53: Der Befehl, der die Waisen findet, die vor der Listener-Reparatur
 * entstanden sind. Gemessen im Bestand: fünf Restaurantbilder aus Februar und
 * Juni plus ein Avatar — alle weiterhin unter ihrer alten Adresse abrufbar.
 */
final class PruneOrphanUploadsCommandTest extends KernelTestCase
{
    /** @var list<string> */
    private array $angelegt = [];

    private string $uploadRoot;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->uploadRoot = static::getContainer()->getParameter('kernel.project_dir').'/public/uploads';
    }

    protected function tearDown(): void
    {
        foreach ($this->angelegt as $pfad) {
            @unlink($pfad);
        }
        parent::tearDown();
    }

    private function waiseAnlegen(string $ordner): string
    {
        $name = 'qa_waise_'.uniqid().'.png';
        $pfad = $this->uploadRoot.'/'.$ordner.'/'.$name;
        file_put_contents($pfad, 'x');
        $this->angelegt[] = $pfad;

        return $name;
    }

    private function tester(): CommandTester
    {
        return new CommandTester(
            (new Application(self::$kernel))->find('app:uploads:prune'),
        );
    }

    public function testOhneForceWirdNurAngezeigt(): void
    {
        $name = $this->waiseAnlegen('restaurants');

        $tester = $this->tester();
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString($name, $tester->getDisplay());
        self::assertFileExists(
            $this->uploadRoot.'/restaurants/'.$name,
            'Ohne --force darf nichts gelöscht werden.',
        );
    }

    public function testMitForceWirdGeloescht(): void
    {
        $name = $this->waiseAnlegen('restaurants');
        $avatar = $this->waiseAnlegen('avatars');

        $tester = $this->tester();
        $tester->execute(['--force' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertFileDoesNotExist($this->uploadRoot.'/restaurants/'.$name);
        self::assertFileDoesNotExist($this->uploadRoot.'/avatars/'.$avatar);
    }

    /**
     * Eine Datei, die zu einer Zeile gehört, bleibt liegen — sonst wäre der
     * Befehl statt einer Aufräumhilfe ein Datenverlust.
     */
    public function testBekannteDateienBleibenUnangetastet(): void
    {
        $verbunden = $this->uploadRoot.'/restaurants/'.$this->connectedFilename();

        $this->tester()->execute(['--force' => true]);

        self::assertFileExists($verbunden);
    }

    private function connectedFilename(): string
    {
        $connection = static::getContainer()->get('doctrine')->getConnection();
        $name = $connection->fetchOne('SELECT filename FROM restaurant_image LIMIT 1');

        if (!\is_string($name) || '' === $name) {
            self::markTestSkipped('Kein Bild in der Datenbank, gegen das geprüft werden könnte.');
        }

        if (!is_file($this->uploadRoot.'/restaurants/'.$name)) {
            file_put_contents($this->uploadRoot.'/restaurants/'.$name, 'x');
            $this->angelegt[] = $this->uploadRoot.'/restaurants/'.$name;
        }

        return $name;
    }
}
