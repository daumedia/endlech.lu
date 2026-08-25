<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * BF-49 + BF-67: Festhalten, wonach jemand tatsächlich gesehen hat.
 *
 * `Restaurant` speichert die Barrierefreiheits-Merkmale als `bool`; dort ist
 * `false` zweierlei zugleich — „gibt es nicht" und „wissen wir nicht". Der
 * Vorschlags-Assistent unterscheidet das seit Langem (`TriState`), bei der
 * Genehmigung ging die Unterscheidung verloren.
 *
 * Auf `/open` war das messbar: Ein Haus ohne jede Angabe hob die ausgewiesene
 * Gemeindeabdeckung (8 → 9) und senkte zugleich die Durchschnittspunktzahl
 * (5,09 → 4,67). Zwei Leitzahlen auf derselben Seite in gegenläufige Richtungen.
 *
 * ⚠ **Der Altbestand gilt als bewertet.** Die elf Häuser stammen aus eigener
 * Recherche des Teams — dort wurde hingesehen, auch wo das Ergebnis „nein" war.
 * Die Alternative wäre, sie alle auf „nicht bewertet" zu setzen und die
 * veröffentlichte Durchschnittspunktzahl über Nacht auf 0 Häuser zu stützen; das
 * wäre keine Korrektur, sondern ein anderer Fehler.
 *
 * ⚠ **Drei Schritte statt eines DEFAULT.** `ADD … JSON NOT NULL DEFAULT
 * (JSON_ARRAY())` ist MySQL-8-Syntax: MariaDB 10.5 kennt keine
 * Ausdrucks-Defaults auf JSON-Spalten, und der Deploy führt jede Migration dort
 * aus. Also erst nullable anlegen, dann füllen, dann festziehen — das versteht
 * beides.
 */
final class Version20260825150000 extends AbstractMigration
{
    private const FEATURES = '["wheelchair","toilet","dogs","lighting","changing_table","disabled_parking","door_width","table_spacing"]';

    public function getDescription(): string
    {
        return 'Fügt restaurant.assessed_features hinzu (BF-49, BF-67)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD assessed_features JSON DEFAULT NULL');
        $this->addSql('UPDATE restaurant SET assessed_features = :features', ['features' => self::FEATURES]);
        $this->addSql('ALTER TABLE restaurant MODIFY assessed_features JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP assessed_features');
    }
}
