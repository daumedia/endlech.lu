<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Standortangaben am Vorschlag (QA B23, BF-24).
 *
 * `POST /api/v1/restaurants` legt ab sofort einen Vorschlag an statt eines
 * öffentlichen Eintrags. Die API nimmt Koordinaten entgegen und prüft sie;
 * ohne diese Spalten gingen sie zwischen Eingang und Freigabe verloren.
 *
 * Der Web-Wizard fragt sie nicht ab — die Spalten bleiben dort leer.
 *
 * Reine ADD COLUMN auf nullable, läuft auch auf MariaDB 10.5.
 */
final class Version20260824160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'restaurant_suggestion: latitude, longitude, nearby_stops_note';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion ADD latitude NUMERIC(10, 8) DEFAULT NULL, ADD longitude NUMERIC(11, 8) DEFAULT NULL, ADD nearby_stops_note LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion DROP latitude, DROP longitude, DROP nearby_stops_note');
    }
}
