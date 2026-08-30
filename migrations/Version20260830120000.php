<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Community Feedback Board (Feature 06): zwei neue Tabellen.
 *
 * `board_idea` — eine Idee zur Plattform. Sichtbarkeit hängt an `published_at`
 * (`NULL` = wartet auf Freigabe), nicht am Status. `submitted_by_id` ist
 * `SET NULL`: Die Idee überlebt die Löschung ihres Verfassers (AK-65).
 *
 * `board_vote` — eine Zustimmung. Beide Fremdschlüssel sind `CASCADE`: Eine
 * Stimme ist die Handlung einer Person und ohne sie bedeutungslos (AK-66). Der
 * Unique-Index über `(idea_id, user_id)` ist die letzte Instanz gegen eine
 * doppelte Zustimmung (AK-20).
 *
 * ⚠ **Von Hand geschrieben, nicht aus `migrations:diff` übernommen.** Das Diff
 * schlägt in diesem Projekt zwölf Index-Umbenennungen aus Altlasten vor, die
 * mit diesem Feature nichts zu tun haben; sie sind hier bewusst nicht enthalten.
 *
 * ⚠ **Gegen MariaDB 10.5 lauffähig** (Production): nur gewöhnliche Spalten,
 * Fremdschlüssel und Indizes. Kein natives ENUM, keine JSON-Funktion, keine
 * Fensterfunktion.
 */
final class Version20260830120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Feature 06: board_idea und board_vote';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE board_idea (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(120) NOT NULL, description LONGTEXT NOT NULL, slug VARCHAR(160) NOT NULL, status VARCHAR(20) NOT NULL, locale VARCHAR(5) NOT NULL, team_response LONGTEXT DEFAULT NULL, published_at DATETIME DEFAULT NULL, notified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, submitted_by_id INT DEFAULT NULL, duplicate_of_id INT DEFAULT NULL, INDEX IDX_D01D936679F7D87D (submitted_by_id), INDEX IDX_D01D93662CC33300 (duplicate_of_id), INDEX IDX_board_idea_public (published_at, status), INDEX IDX_board_idea_queue (published_at, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE board_vote (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, idea_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8086DC475B6FEF7D (idea_id), INDEX IDX_8086DC47A76ED395 (user_id), UNIQUE INDEX UNIQ_board_vote_idea_user (idea_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE board_idea ADD CONSTRAINT FK_D01D936679F7D87D FOREIGN KEY (submitted_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE board_idea ADD CONSTRAINT FK_D01D93662CC33300 FOREIGN KEY (duplicate_of_id) REFERENCES board_idea (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE board_vote ADD CONSTRAINT FK_8086DC475B6FEF7D FOREIGN KEY (idea_id) REFERENCES board_idea (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE board_vote ADD CONSTRAINT FK_8086DC47A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE board_vote DROP FOREIGN KEY FK_8086DC475B6FEF7D');
        $this->addSql('ALTER TABLE board_vote DROP FOREIGN KEY FK_8086DC47A76ED395');
        $this->addSql('ALTER TABLE board_idea DROP FOREIGN KEY FK_D01D936679F7D87D');
        $this->addSql('ALTER TABLE board_idea DROP FOREIGN KEY FK_D01D93662CC33300');
        $this->addSql('DROP TABLE board_vote');
        $this->addSql('DROP TABLE board_idea');
    }
}
