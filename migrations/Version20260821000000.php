<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Passkeys: webauthn_credential-Tabelle und user.webauthn_handle';
    }

    public function up(Schema $schema): void
    {
        // Die Spaltentypen der geerbten Felder gibt das WebAuthn-Bundle vor
        // (mapped-superclass Webauthn\CredentialRecord plus die DBAL-Typen
        // base64/aaguid/trust_path). Deshalb LONGTEXT statt BINARY: Der Typ
        // `base64` kodiert vor dem Schreiben und deklariert sich als CLOB.
        //
        // JSON ist auf MariaDB 10.5 – der Datenbank auf Production – ein Alias
        // auf LONGTEXT und damit unbedenklich, wie schon in
        // Version20260820200000.
        $this->addSql('CREATE TABLE webauthn_credential (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            public_key_credential_id LONGTEXT NOT NULL,
            type VARCHAR(255) NOT NULL,
            transports JSON NOT NULL,
            attestation_type VARCHAR(255) NOT NULL,
            trust_path JSON NOT NULL,
            aaguid TINYTEXT NOT NULL,
            credential_public_key LONGTEXT NOT NULL,
            user_handle VARCHAR(255) NOT NULL,
            counter INT NOT NULL,
            other_ui JSON DEFAULT NULL,
            backup_eligible TINYINT DEFAULT NULL,
            backup_status TINYINT DEFAULT NULL,
            uv_initialized TINYINT DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            last_used_at DATETIME DEFAULT NULL,
            INDEX IDX_850123F9A76ED395 (user_id),
            INDEX IDX_webauthn_credential_id (public_key_credential_id(100)),
            INDEX IDX_webauthn_credential_handle (user_handle),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Die Kennung wird bei jeder Anmeldung nachgeschlagen, liegt aber als
        // LONGTEXT vor und ist daher nur mit Längenangabe indizierbar. 100
        // Zeichen trennen die Einträge zuverlässig.
        //
        // CASCADE statt SET NULL: Ein Passkey ohne Konto liesse sich weder
        // benutzen noch im Profil löschen – er wäre nur noch ein Datenrest.
        $this->addSql('ALTER TABLE webauthn_credential
            ADD CONSTRAINT FK_850123F9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        // NULL-bar: Der Handle entsteht erst, wenn jemand seinen ersten Passkey
        // anlegt. Ein Wert für alle Bestandskonten wäre eine Datenmigration für
        // etwas, das die meisten nie benutzen. Mehrere NULL sind im UNIQUE-Index
        // sowohl in MySQL als auch in MariaDB erlaubt.
        $this->addSql('ALTER TABLE `user` ADD webauthn_handle VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649387A2A25 ON `user` (webauthn_handle)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649387A2A25 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP webauthn_handle');
        $this->addSql('ALTER TABLE webauthn_credential DROP FOREIGN KEY FK_850123F9A76ED395');
        $this->addSql('DROP TABLE webauthn_credential');
    }
}
