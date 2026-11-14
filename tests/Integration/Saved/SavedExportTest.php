<?php

declare(strict_types=1);

namespace App\Tests\Integration\Saved;

use App\Account\AccountDataExporter;
use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 17, T11: Favoriten, eigene Listen und Notizen stehen im Datenexport (AK-60,
 * Art. 15/20 DSGVO). Rein ergänzend — die Formatkennung bleibt `v1`.
 *
 * Vor dem Export wird der EntityManager geleert und das Konto neu geladen: Der Export soll
 * nachweislich aus der Datenbank lesen, nicht aus Objekten, die der Test noch in der Hand hält.
 */
final class SavedExportTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testAk60ExportEnthaeltFavoritenListeUndNotiz(): void
    {
        $konto = $this->konto('export-voll@example.test');
        $a = $this->lokal('Export Lokal A');
        $b = $this->lokal('Export Lokal B');
        $c = $this->lokal('Export Lokal C');

        $favoritA = new Favorite($konto, $a);
        $favoritB = new Favorite($konto, $b);
        $liste = new SavedList($konto, 'Mit Oma', 'mit oma');
        $eintraege = [new SavedListEntry($liste, $a), new SavedListEntry($liste, $b), new SavedListEntry($liste, $c)];
        $notiz = new RestaurantNote($konto, $c, 'Seiteneingang links');

        foreach ([$favoritA, $favoritB, $liste, ...$eintraege, $notiz] as $objekt) {
            $this->em->persist($objekt);
        }
        $this->em->flush();

        $export = $this->exportiere($konto);

        // Rein ergänzend: Die Formatkennung bleibt, wie sie war.
        self::assertSame('endlech.lu account export v1', $export['format']);
        // Alles muss durch `json_encode` — der Controller liefert den Export als Datei.
        json_encode($export, \JSON_THROW_ON_ERROR);

        // Zwei Favoriten, je Lokal und Zeitpunkt.
        self::assertArrayHasKey('favorites', $export);
        self::assertCount(2, $export['favorites']);
        foreach ($export['favorites'] as $zeile) {
            self::assertSame(['restaurantId', 'restaurantName', 'savedAt'], array_keys($zeile));
            $this->assertAtom($zeile['savedAt']);
        }
        self::assertEqualsCanonicalizing(
            [$a->getId(), $b->getId()],
            array_column($export['favorites'], 'restaurantId'),
        );
        self::assertEqualsCanonicalizing(
            ['Export Lokal A', 'Export Lokal B'],
            array_column($export['favorites'], 'restaurantName'),
        );
        self::assertEqualsCanonicalizing(
            [$favoritA->getCreatedAt()->format(\DateTimeInterface::ATOM), $favoritB->getCreatedAt()->format(\DateTimeInterface::ATOM)],
            array_column($export['favorites'], 'savedAt'),
        );

        // Eine Liste: Name, angelegt am, die drei Lokale je mit Zeitpunkt — keine interne Nummer.
        self::assertArrayHasKey('savedLists', $export);
        self::assertCount(1, $export['savedLists']);
        $exportListe = $export['savedLists'][0];
        self::assertSame(['name', 'createdAt', 'restaurants'], array_keys($exportListe));
        self::assertSame('Mit Oma', $exportListe['name']);
        self::assertSame($liste->getCreatedAt()->format(\DateTimeInterface::ATOM), $exportListe['createdAt']);
        $this->assertAtom($exportListe['createdAt']);
        self::assertCount(3, $exportListe['restaurants']);
        foreach ($exportListe['restaurants'] as $zeile) {
            self::assertSame(['restaurantId', 'restaurantName', 'addedAt'], array_keys($zeile));
            $this->assertAtom($zeile['addedAt']);
        }
        self::assertEqualsCanonicalizing(
            [$a->getId(), $b->getId(), $c->getId()],
            array_column($exportListe['restaurants'], 'restaurantId'),
        );
        self::assertEqualsCanonicalizing(
            ['Export Lokal A', 'Export Lokal B', 'Export Lokal C'],
            array_column($exportListe['restaurants'], 'restaurantName'),
        );
        self::assertEqualsCanonicalizing(
            array_map(static fn (SavedListEntry $e): string => $e->getAddedAt()->format(\DateTimeInterface::ATOM), $eintraege),
            array_column($exportListe['restaurants'], 'addedAt'),
        );

        // Eine Notiz: Lokal, Text, zuletzt geändert.
        self::assertArrayHasKey('notes', $export);
        self::assertCount(1, $export['notes']);
        $exportNotiz = $export['notes'][0];
        self::assertSame(['restaurantId', 'restaurantName', 'text', 'updatedAt'], array_keys($exportNotiz));
        self::assertSame($c->getId(), $exportNotiz['restaurantId']);
        self::assertSame('Export Lokal C', $exportNotiz['restaurantName']);
        self::assertSame('Seiteneingang links', $exportNotiz['text']);
        self::assertSame($notiz->getUpdatedAt()->format(\DateTimeInterface::ATOM), $exportNotiz['updatedAt']);
        $this->assertAtom($exportNotiz['updatedAt']);
    }

    public function testAk60KontoOhneGemerktesBekommtDreiLeereListen(): void
    {
        // Ein fremdes Konto mit Bestand daneben: Der Export darf davon nichts zeigen.
        $fremd = $this->konto('export-fremd@example.test');
        $lokal = $this->lokal('Export Lokal Fremd');
        $liste = new SavedList($fremd, 'Fremde Liste', 'fremde liste');
        foreach ([new Favorite($fremd, $lokal), $liste, new SavedListEntry($liste, $lokal), new RestaurantNote($fremd, $lokal, 'Fremde Notiz')] as $objekt) {
            $this->em->persist($objekt);
        }
        $this->em->flush();

        $leer = $this->konto('export-leer@example.test');

        $export = $this->exportiere($leer);

        self::assertSame('endlech.lu account export v1', $export['format']);
        self::assertSame([], $export['favorites']);
        self::assertSame([], $export['savedLists']);
        self::assertSame([], $export['notes']);
    }

    /**
     * @return array<string, mixed>
     */
    private function exportiere(User $konto): array
    {
        $id = $konto->getId();
        $this->em->clear();
        $neuGeladen = $this->em->find(User::class, $id);
        self::assertNotNull($neuGeladen);

        return static::getContainer()->get(AccountDataExporter::class)->export($neuGeladen);
    }

    private function assertAtom(mixed $wert): void
    {
        self::assertIsString($wert);
        $gelesen = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $wert);
        self::assertNotFalse($gelesen, sprintf('„%s“ ist kein Zeitpunkt im ATOM-Format.', $wert));
        self::assertSame($wert, $gelesen->format(\DateTimeInterface::ATOM));
    }

    private function konto(string $email): User
    {
        $user = (new User())->setName('Export')->setEmail($email)->setPassword('x')->setIsVerified(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function lokal(string $name): Restaurant
    {
        $restaurant = (new Restaurant())->setName($name)->setCity('Luxembourg');
        $this->em->persist($restaurant);
        $this->em->flush();

        return $restaurant;
    }
}
