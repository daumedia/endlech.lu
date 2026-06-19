<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow multiple opening_hour slots per day: drop unique (restaurant, day) constraint and is_closed column';
    }

    public function up(Schema $schema): void
    {
        // Geschlossene Tage werden im neuen Modell durch das Fehlen von Slots abgebildet.
        $this->addSql('DELETE FROM opening_hour WHERE is_closed = 1');
        $this->addSql('DROP INDEX unique_restaurant_day ON opening_hour');
        $this->addSql('ALTER TABLE opening_hour DROP is_closed');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE opening_hour ADD is_closed TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_restaurant_day ON opening_hour (restaurant_id, day_of_week)');
    }
}
