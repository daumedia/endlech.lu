<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tabelle für den persistenten Merkposten der Zeitpläne (App\Scheduler\*).
 *
 * Der Pool `cache.scheduler` liegt in der Datenbank statt unter var/cache: Dort
 * überlebt er `cache:clear` und damit jeden Deploy. Ohne das gälte nach jedem
 * Ausrollen „letzter Lauf: jetzt", und ein Monatslauf, der genau in dieses
 * Fenster fiel, wäre ohne Fehlermeldung verloren.
 *
 * ⚠ Die Struktur folgt exakt `DoctrineDbalAdapter::addTableToSchema()` —
 * VARBINARY für den Schlüssel (nicht VARCHAR: der Adapter deklariert auf MySQL
 * `binary`), MEDIUMBLOB für die Nutzlast (Länge 16777215) und vorzeichenlose
 * Ganzzahlen für Frist und Zeitpunkt. Weicht eine Spalte ab, schreibt der
 * Adapter still an der Tabelle vorbei oder scheitert beim ersten Zugriff.
 *
 * ⚠ Reines CREATE TABLE ohne MySQL-8-Syntax — Production läuft auf MariaDB.
 *
 * ⚠ **`IF NOT EXISTS` ist hier Pflicht, nicht Bequemlichkeit.** Diese Tabelle hat
 * ZWEI mögliche Erzeuger: diese Migration und `DoctrineDbalAdapter`, der sie beim
 * ersten Schreibzugriff selbst anlegt, wenn sie fehlt. Wer den Worker startet,
 * bevor die Migration lief — beim Umzug auf ein neues Hosting der Normalfall, weil
 * der Consumer sofort seinen Merkposten schreibt — hat die Tabelle danach, aber
 * keinen Eintrag in `doctrine_migration_versions`. Die Migration scheitert dann mit
 * `SQLSTATE[42S01] … Table 'cache_items' already exists`, **und zwar bei jedem
 * weiteren Deploy**, bis jemand von Hand eingreift.
 *
 * Gemessen am 2026-09-02 auf Production. Der Handgriff zum Aufräumen ist
 * `doctrine:migrations:version 'DoctrineMigrations\Version20260902200000' --add`;
 * diese Zeile sorgt dafür, dass es ihn kein zweites Mal braucht.
 */
final class Version20260902200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Legt cache_items für den persistenten Merkposten der Zeitpläne an';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS cache_items (
                item_id VARBINARY(255) NOT NULL,
                item_data MEDIUMBLOB NOT NULL,
                item_lifetime INT UNSIGNED DEFAULT NULL,
                item_time INT UNSIGNED NOT NULL,
                PRIMARY KEY(item_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS cache_items');
    }
}
