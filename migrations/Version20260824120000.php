<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ausstehende E-Mail-Änderung: Die neue Adresse wird vorgemerkt statt sofort
 * übernommen. Ohne das genügte eine gekaperte Sitzung für eine dauerhafte
 * Kontoübernahme (QA B04, BUG-15).
 *
 * Reine ADD COLUMN auf nullable – läuft auch auf MariaDB 10.5, wie sie auf
 * Production steht.
 */
final class Version20260824120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'user: pending_email, pending_email_token, pending_email_token_expires_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD pending_email VARCHAR(180) DEFAULT NULL, ADD pending_email_token VARCHAR(64) DEFAULT NULL, ADD pending_email_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP pending_email, DROP pending_email_token, DROP pending_email_token_expires_at');
    }
}
