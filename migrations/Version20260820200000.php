<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Open-Startup-Seite: finance_entry, metric_snapshot und die Maßangaben am Restaurant';
    }

    public function up(Schema $schema): void
    {
        // Nur Basis-DDL, keine MySQL-8-Syntax: Production läuft auf MariaDB
        // 10.5, lokal und in der CI dagegen MySQL 8.0. JSON ist dort ein Alias
        // auf LONGTEXT und damit unbedenklich – so hält es auch schon
        // Version20260820100000.
        $this->addSql('CREATE TABLE finance_entry (
            id INT AUTO_INCREMENT NOT NULL,
            entry_date DATE NOT NULL,
            type VARCHAR(20) NOT NULL,
            category VARCHAR(40) NOT NULL,
            amount NUMERIC(10, 2) NOT NULL,
            quantity INT DEFAULT NULL,
            note LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_finance_entry_type_date (type, entry_date),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // captured_for ist eindeutig: Der Monatslauf ist damit auf
        // Datenbankebene idempotent und nicht nur im Anwendungscode.
        $this->addSql('CREATE TABLE metric_snapshot (
            id INT AUTO_INCREMENT NOT NULL,
            captured_for DATE NOT NULL,
            restaurant_count INT NOT NULL,
            verified_count INT NOT NULL,
            communes_covered INT NOT NULL,
            cantons_covered INT NOT NULL,
            average_accessibility_score NUMERIC(4, 2) NOT NULL,
            step_free_entrances INT NOT NULL,
            accessible_restrooms INT NOT NULL,
            wide_doors INT NOT NULL,
            wheelchair_table_spacing INT NOT NULL,
            inclusion_boxes_delivered INT NOT NULL,
            total_expenses NUMERIC(12, 2) NOT NULL,
            total_income NUMERIC(12, 2) NOT NULL,
            payload JSON NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_metric_snapshot_month (captured_for),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // NULL-bar ohne Default: null heißt "nicht ausgemessen". Ein
        // 0-Default würde jedes bestehende Restaurant als Negativbefund in die
        // veröffentlichte Impact-Zahl schreiben.
        $this->addSql('ALTER TABLE restaurant
            ADD door_width_cm INT DEFAULT NULL,
            ADD table_spacing_cm INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP door_width_cm, DROP table_spacing_cm');
        $this->addSql('DROP TABLE metric_snapshot');
        $this->addSql('DROP TABLE finance_entry');
    }
}
