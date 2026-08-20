<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organisation_waitlist_entry table for communes, companies and associations';
    }

    public function up(Schema $schema): void
    {
        // Nur Basis-DDL: Production läuft auf MariaDB 10.5, lokal und in der CI
        // dagegen MySQL 8.0. Kein COMMENT '(DC2Type:…)' – DBAL 4 erwartet ihn
        // nicht mehr und er erzeugte sonst dauerhaft eine Abweichung in
        // doctrine:schema:validate.
        //
        // Die typspezifischen Spalten sind alle NULL-bar; welche davon gefüllt
        // sein dürfen, entscheidet die Validierungsgruppe anhand von `type`.
        $this->addSql("CREATE TABLE organisation_waitlist_entry (
            id INT AUTO_INCREMENT NOT NULL,
            type VARCHAR(20) NOT NULL,
            organisation_name VARCHAR(180) NOT NULL,
            contact_name VARCHAR(120) NOT NULL,
            contact_role VARCHAR(120) DEFAULT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(40) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            message LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            confirmation_token VARCHAR(64) DEFAULT NULL,
            confirmed_at DATETIME DEFAULT NULL,
            consent_at DATETIME NOT NULL,
            locale VARCHAR(5) NOT NULL,
            source VARCHAR(60) DEFAULT NULL,
            commune_name VARCHAR(120) DEFAULT NULL,
            estimated_venues INT DEFAULT NULL,
            timeframe VARCHAR(20) DEFAULT NULL,
            sponsorship_interests JSON NOT NULL,
            collaboration_interests JSON NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_2D7E1C2FC05FB297 (confirmation_token),
            INDEX IDX_org_waitlist_type_status (type, status),
            INDEX IDX_org_waitlist_status_created (status, created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organisation_waitlist_entry');
    }
}
