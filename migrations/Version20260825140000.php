<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Feature 01: Token zum Zurücksetzen des Passworts.
 *
 * Bis heute gibt es keinen Weg zurück in ein Konto, dessen Passwort vergessen
 * ist — und seit der BF-19-Reparatur wird eine E-Mail-Änderung nur noch nach
 * Bestätigung wirksam. Beides zusammen ergibt eine Sackgasse, die real und
 * heute offen ist.
 *
 * Reine `ADD COLUMN`, nullable — MariaDB-10.5-tauglich (Production).
 */
final class Version20260825140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fügt user.password_reset_token und -_expires_at hinzu (Feature 01)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD password_reset_token VARCHAR(64) DEFAULT NULL, ADD password_reset_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP password_reset_token, DROP password_reset_token_expires_at');
    }
}
