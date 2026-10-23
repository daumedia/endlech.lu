<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Feature 12 — zwei Angaben zur Einführung am Konto.
 *
 * Reines `ADD COLUMN`, kein Datenumbau. Gerade das ist der Punkt: Bestandskonten tragen danach
 * `onboarding_due = 0` und bekommen die Einführung nie (AK-06); den Merker setzt erst der
 * Bestätigungslink, den sie längst hinter sich haben. Konten, die am Tag der Auslieferung noch
 * unbestätigt sind, bekommen ihn mit ihrer Bestätigung (AK-07).
 *
 * ⚠ `doctrine:migrations:diff` erzeugte zusätzlich Index-Umbenennungen und Spaltenkommentare an
 * sechs anderen Tabellen — Abweichungen zwischen Mapping und älteren Migrationen, die mit diesem
 * Feature nichts zu tun haben. Sie stehen bewusst nicht hier.
 */
final class Version20261022203034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Onboarding: user.onboarding_due and user.onboarding_shown_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD onboarding_due TINYINT DEFAULT 0 NOT NULL, ADD onboarding_shown_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP onboarding_due, DROP onboarding_shown_at');
    }
}
