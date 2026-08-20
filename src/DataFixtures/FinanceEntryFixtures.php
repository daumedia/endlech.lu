<?php

namespace App\DataFixtures;

use App\Entity\FinanceEntry;
use App\Enum\FinanceCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Beispielbelege für die Finanzsektion von /open.
 *
 * Die Daten sind erfunden, aber in Größenordnung und Rhythmus realistisch:
 * monatliches Hosting, jährliche Domain, ein Apple-Developer-Beitrag, zwei
 * Materiallieferungen für Inclusion Boxes.
 *
 * Die Einnahmen liegen bewusst so, dass die Quartalssperre in der lokalen
 * Entwicklung greift und man sieht, wie die Seite ohne Einnahmenblock
 * aussieht: Der früheste Einnahmeposten liegt im laufenden Quartal.
 */
class FinanceEntryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('first day of this month midnight');

        // Laufende Kosten der letzten zwölf Monate.
        for ($monthsAgo = 12; $monthsAgo >= 1; --$monthsAgo) {
            $date = $now->modify(sprintf('-%d months', $monthsAgo));

            $manager->persist($this->entry($date, FinanceCategory::HOSTING, '29.00', 'Cloudways – Monatspauschale'));
            $manager->persist($this->entry($date, FinanceCategory::EMAIL, '9.00', 'Brevo – Versandkontingent'));
        }

        $manager->persist($this->entry($now->modify('-10 months'), FinanceCategory::DOMAIN, '38.50', 'endlech.lu – Jahresgebühr'));
        $manager->persist($this->entry($now->modify('-8 months'), FinanceCategory::APPLE_DEVELOPER, '99.00', 'Apple Developer Program – Jahresbeitrag'));
        $manager->persist($this->entry($now->modify('-6 months'), FinanceCategory::OTHER_EXPENSE, '64.20', 'Porto und Druck für den Erstkontakt mit Gemeinden'));

        // Inclusion Boxes: Die Stückzahl hängt am Materialbeleg.
        $manager->persist($this->entry($now->modify('-5 months'), FinanceCategory::INCLUSION_BOX_MATERIALS, '420.00', 'Erste Serie Inclusion Boxes', 12));
        $manager->persist($this->entry($now->modify('-2 months'), FinanceCategory::INCLUSION_BOX_MATERIALS, '245.00', 'Nachlieferung Piktogramm-Sets', 7));

        // Einnahmen im laufenden Quartal – noch nicht veröffentlichungsreif.
        $manager->persist($this->entry($now, FinanceCategory::DONATION, '150.00', 'Spende nach dem Workshop in Esch'));
        $manager->persist($this->entry($now, FinanceCategory::SPONSORSHIP, '500.00', 'Sponsoring – Pilotphase'));

        $manager->flush();
    }

    private function entry(
        \DateTimeImmutable $date,
        FinanceCategory $category,
        string $amount,
        string $note,
        ?int $quantity = null,
    ): FinanceEntry {
        $entry = new FinanceEntry();
        $entry->setDate($date);
        // setCategory setzt die Richtung mit – deshalb vor setQuantity, sonst
        // räumt der Kategoriewechsel die Stückzahl gleich wieder weg.
        $entry->setCategory($category);
        $entry->setAmount($amount);
        $entry->setQuantity($quantity);
        $entry->setNote($note);

        return $entry;
    }
}
