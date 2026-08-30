<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Feature 04 · Marketing-Kontakte in Brevo.
 *
 * Legt das Auftragsbuch `marketing_contact` an und ergänzt die drei Quellen um
 * den Zeitpunkt der Werbe-Einwilligung.
 *
 * ⚠ **`marketing_contact` hat bewusst keinen Fremdschlüssel.** Das ist die
 * einzige Abweichung dieses Features von der `ON DELETE`-Konvention des
 * Projekts. Der Grund: Ein Wartelisten-Widerruf löscht den Eintrag; hinge der
 * Löschauftrag an ihm, verschwände er mit seiner Quelle – und die Adresse
 * bliebe für immer in Brevo stehen. Der Auftrag muss die Löschung seiner
 * Quelle überleben. Die Verbindung läuft über die E-Mail-Adresse, die zugleich
 * eindeutig ist: eine Adresse, ein Kontakt (EC-01) – auf Datenbankebene, nicht
 * als Anwendungslogik.
 *
 * ⚠ **Kein Feld für die Freitextnachricht der Wartelisten.** Dort kann auf
 * einer Barrierefreiheitsplattform eine Gesundheitsangabe stehen und damit eine
 * besondere Kategorie nach Art. 9 DSGVO. Was die Tabelle nicht führt, kann
 * nicht nach Brevo abfließen (AK-29).
 *
 * MariaDB-10.5-tauglich: eine `CREATE TABLE` ohne natives `ENUM` (die beiden
 * Aufzählungen sind `VARCHAR` mit `enumType` im Mapping) und drei reine
 * `ADD COLUMN` auf nullable. Production ist MariaDB, lokal und CI sind MySQL 8.
 */
final class Version20260829120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Feature 04: marketing_contact + marketing_consent_at an den drei Quellen';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE marketing_contact (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            contact_name VARCHAR(120) DEFAULT NULL,
            organisation_name VARCHAR(180) DEFAULT NULL,
            locale VARCHAR(5) NOT NULL,
            origin VARCHAR(20) NOT NULL,
            funnel_status VARCHAR(20) DEFAULT NULL,
            consent_at DATETIME NOT NULL,
            revoked_at DATETIME DEFAULT NULL,
            sync_state VARCHAR(20) NOT NULL,
            synced_at DATETIME DEFAULT NULL,
            last_error VARCHAR(255) DEFAULT NULL,
            attempts SMALLINT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_E78FBDB7E7927C74 (email),
            INDEX IDX_marketing_contact_state_updated (sync_state, updated_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Der Zeitpunkt steht an der Quelle **und** im Auftragsbuch, und das ist
        // keine Redundanz: Die Quelle trägt den Nachweis der Einwilligung
        // (Art. 7 Abs. 1 DSGVO) und speist den Datenexport; das Auftragsbuch
        // trägt den Auftrag und muss die Quelle überleben.
        $this->addSql('ALTER TABLE partner_waitlist_entry ADD marketing_consent_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE organisation_waitlist_entry ADD marketing_consent_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD marketing_consent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner_waitlist_entry DROP marketing_consent_at');
        $this->addSql('ALTER TABLE organisation_waitlist_entry DROP marketing_consent_at');
        $this->addSql('ALTER TABLE `user` DROP marketing_consent_at');
        $this->addSql('DROP TABLE marketing_contact');
    }
}
