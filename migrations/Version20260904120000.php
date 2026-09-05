<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Warteliste für die mobile App (Feature 08).
 *
 * Die dritte Warteliste neben `partner_waitlist_entry` (B14) und
 * `organisation_waitlist_entry` (B15) und die schmalste: Adresse, Plattform,
 * die üblichen Zeitstempel des Double-Opt-In. Kein Name, kein Telefon, kein
 * Freitext — was nicht erfasst ist, kann nicht versehentlich veröffentlicht
 * werden.
 *
 * ⚠ **Von Hand geschrieben, nicht aus `migrations:diff`.** Der Diff schlägt in
 * diesem Projekt regelmäßig Index-Umbenennungen aus Altlasten vor, die mit der
 * Änderung nichts zu tun haben.
 *
 * ⚠ **Der Unique-Index auf `email` ist der Unterschied zu den beiden anderen
 * Wartelisten.** Dort legt jeder Submit eine weitere Zeile an. Hier ist „eine
 * Adresse, ein Eintrag" ein Akzeptanzkriterium (AK-15), und eine Prüfung allein
 * im Controller verlöre das Wettrennen zweier gleichzeitiger Absendevorgänge.
 *
 * ⚠ **Kein natives ENUM für `platform` und `status`, kein CHECK-Constraint** —
 * Production läuft auf MariaDB, lokal und in der CI MySQL 8. Die Gültigkeit
 * sichert das Doctrine-Enum-Mapping, so wie bei allen anderen Enum-Spalten
 * dieses Projekts.
 *
 * ⚠ **Keine `COMMENT '(DC2Type:…)'`-Zusätze an den Zeitstempeln.** Doctrine 3
 * schreibt sie nicht mehr; ältere Tabellen dieses Projekts tragen sie noch und
 * erzeugen deshalb bei jedem `doctrine:schema:update --dump-sql` Rauschen. Eine
 * neue Tabelle vergrößert es nicht.
 */
final class Version20260904120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Feature 08: Tabelle app_waitlist_entry (Warteliste für die mobile App)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE app_waitlist_entry (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                platform VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL,
                confirmation_token VARCHAR(64) DEFAULT NULL,
                confirmed_at DATETIME DEFAULT NULL,
                self_confirmed_at DATETIME DEFAULT NULL,
                consent_at DATETIME NOT NULL,
                marketing_consent_at DATETIME DEFAULT NULL,
                beta_link_sent_at DATETIME DEFAULT NULL,
                locale VARCHAR(5) NOT NULL,
                source VARCHAR(60) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_APP_WAITLIST_EMAIL (email),
                UNIQUE INDEX UNIQ_APP_WAITLIST_TOKEN (confirmation_token),
                INDEX IDX_app_waitlist_status_created (status, created_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_waitlist_entry');
    }
}
