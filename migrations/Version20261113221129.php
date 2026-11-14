<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Feature 17 — Favoriten und Listen: vier neue Tabellen, keine Änderung an bestehenden.
 *
 * Alle Fremdschlüssel kaskadieren: Verschwindet ein Konto oder ein Lokal, verschwindet alles
 * daran in der Datenbank (AK-61, AK-62), ohne dass Kontolöschung oder Restaurantverwaltung
 * etwas dafür tun. `saved_list.name_key` trägt eine **binäre** Kollation — der eindeutige Index
 * soll „Groß/klein egal“ prüfen, nicht auch „Akzent egal“ (`utf8mb4_0900_ai_ci`), siehe
 * `App\Entity\SavedList`.
 *
 * Rein ergänzend (`CREATE TABLE`). ⚠ Der neue Code liest `favorite` auf jeder Such- und
 * Detailseite eines angemeldeten Kontos — die Migration muss vor dem Containertausch laufen
 * (`design.md`, Migration).
 *
 * ⚠ `doctrine:migrations:diff` erzeugte gegen die lokale Entwicklungsdatenbank zusätzlich das
 * halbe Schema (sie stand weit hinter dem Migrationsstand). Hier steht nur, was zu Feature 17
 * gehört.
 */
final class Version20261113221129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Feature 17: favorite, saved_list, saved_list_entry, restaurant_note';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE favorite (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, restaurant_id INT NOT NULL, INDEX IDX_68C58ED9A76ED395 (user_id), INDEX IDX_68C58ED9B1E7706E (restaurant_id), INDEX IDX_favorite_user_created (user_id, created_at), UNIQUE INDEX UNIQ_favorite_user_restaurant (user_id, restaurant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE restaurant_note (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(500) NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, restaurant_id INT NOT NULL, INDEX IDX_DAB6A70A76ED395 (user_id), INDEX IDX_DAB6A70B1E7706E (restaurant_id), UNIQUE INDEX UNIQ_restaurant_note_user_restaurant (user_id, restaurant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE saved_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, name_key VARCHAR(60) NOT NULL COLLATE `utf8mb4_bin`, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_4AF70A0DA76ED395 (user_id), INDEX IDX_saved_list_user_created (user_id, created_at), UNIQUE INDEX UNIQ_saved_list_user_name (user_id, name_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE saved_list_entry (id INT AUTO_INCREMENT NOT NULL, added_at DATETIME NOT NULL, list_id INT NOT NULL, restaurant_id INT NOT NULL, INDEX IDX_318B5C9B3DAE168B (list_id), INDEX IDX_318B5C9BB1E7706E (restaurant_id), INDEX IDX_saved_list_entry_list_added (list_id, added_at), UNIQUE INDEX UNIQ_saved_list_entry_list_restaurant (list_id, restaurant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE restaurant_note ADD CONSTRAINT FK_DAB6A70A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE restaurant_note ADD CONSTRAINT FK_DAB6A70B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE saved_list ADD CONSTRAINT FK_4AF70A0DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE saved_list_entry ADD CONSTRAINT FK_318B5C9B3DAE168B FOREIGN KEY (list_id) REFERENCES saved_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE saved_list_entry ADD CONSTRAINT FK_318B5C9BB1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A76ED395');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9B1E7706E');
        $this->addSql('ALTER TABLE restaurant_note DROP FOREIGN KEY FK_DAB6A70A76ED395');
        $this->addSql('ALTER TABLE restaurant_note DROP FOREIGN KEY FK_DAB6A70B1E7706E');
        $this->addSql('ALTER TABLE saved_list DROP FOREIGN KEY FK_4AF70A0DA76ED395');
        $this->addSql('ALTER TABLE saved_list_entry DROP FOREIGN KEY FK_318B5C9B3DAE168B');
        $this->addSql('ALTER TABLE saved_list_entry DROP FOREIGN KEY FK_318B5C9BB1E7706E');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('DROP TABLE restaurant_note');
        $this->addSql('DROP TABLE saved_list');
        $this->addSql('DROP TABLE saved_list_entry');
    }
}
