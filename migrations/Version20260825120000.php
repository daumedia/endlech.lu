<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * BF-55: Sprache des Einreichers am Vorschlag festhalten.
 *
 * Ohne sie ließe sich die Ablehnungsmail nicht in der Sprache schreiben, in der
 * jemand den Assistenten ausgefüllt hat: `User` führt keine Sprache, und die des
 * Admins wäre die falsche. Beide Wartelisten haben aus demselben Grund dasselbe
 * Feld.
 *
 * Reine `ADD COLUMN` mit Default — MariaDB-10.5-tauglich (Production).
 * Der Default `de` ist für den Altbestand: Bis heute lief der Assistent
 * praktisch nur auf Deutsch, und eine Nachricht auf Deutsch ist besser als eine
 * auf Luxemburgisch an jemanden, der Französisch spricht.
 */
final class Version20260825120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fügt restaurant_suggestion.locale hinzu (BF-55)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE restaurant_suggestion ADD locale VARCHAR(5) DEFAULT 'de' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion DROP locale');
    }
}
