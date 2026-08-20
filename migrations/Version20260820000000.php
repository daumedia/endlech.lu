<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add partner_waitlist_entry table for the partner programme waiting list (double opt-in)';
    }

    public function up(Schema $schema): void
    {
        // Nur Basis-DDL: Production läuft auf MariaDB 10.5, lokal und in der CI
        // dagegen MySQL 8.0 – MySQL-8-only-Syntax fiele erst beim Deploy auf.
        //
        // Bewusst OHNE COMMENT '(DC2Type:datetime_immutable)': Ältere Migrationen
        // im Projekt tragen den Kommentar noch, DBAL 4 erwartet ihn aber nicht
        // mehr. Bei einer neuen Tabelle würde er nur dauerhaft eine Abweichung
        // in doctrine:schema:validate erzeugen.
        $this->addSql("CREATE TABLE partner_waitlist_entry (
            id INT AUTO_INCREMENT NOT NULL,
            restaurant_id INT DEFAULT NULL,
            restaurant_name VARCHAR(180) NOT NULL,
            contact_name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(40) DEFAULT NULL,
            locality VARCHAR(120) NOT NULL,
            message LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            confirmation_token VARCHAR(64) DEFAULT NULL,
            confirmed_at DATETIME DEFAULT NULL,
            consent_at DATETIME NOT NULL,
            locale VARCHAR(5) NOT NULL,
            source VARCHAR(60) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_66D3FC4FC05FB297 (confirmation_token),
            INDEX IDX_66D3FC4FB1E7706E (restaurant_id),
            INDEX IDX_partner_waitlist_status_created (status, created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // SET NULL statt CASCADE: Wird ein Restaurant gelöscht, ist die
        // Anmeldung selbst weiterhin belegbar (Consent-Nachweis, Attribution).
        $this->addSql('ALTER TABLE partner_waitlist_entry
            ADD CONSTRAINT FK_66D3FC4FB1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner_waitlist_entry DROP FOREIGN KEY FK_66D3FC4FB1E7706E');
        $this->addSql('DROP TABLE partner_waitlist_entry');
    }
}
