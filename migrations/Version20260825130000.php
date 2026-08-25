<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * BF-56: Türbreite und Tischabstand am Vorschlag erfassen.
 *
 * Der Assistent erhob die beiden Maße nicht, und ein genehmigtes Haus startete
 * deshalb mit zwei fehlenden von zehn Punkten der Barrierefreiheits-Wertung —
 * ohne dass jemand nachgemessen hätte. `Restaurant` führt die Felder seit
 * Version20260820200000; hier fehlte das Gegenstück.
 *
 * Reine `ADD COLUMN`, nullable — MariaDB-10.5-tauglich. `null` heißt „nicht
 * ausgemessen" und ist ausdrücklich etwas anderes als „zu schmal".
 */
final class Version20260825130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fügt restaurant_suggestion.door_width_cm und table_spacing_cm hinzu (BF-56)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion ADD door_width_cm INT DEFAULT NULL, ADD table_spacing_cm INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion DROP door_width_cm, DROP table_spacing_cm');
    }
}
