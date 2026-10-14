<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Vereine und Unternehmen werden eine Zielgruppe, der Beirat entfällt (2026-10-14).
 *
 * Reine Datenmigration, kein Schemawechsel. Drei Schritte:
 *
 * 1. `organisation_waitlist_entry.type`: `association` → `company`. Der Enum-Fall
 *    ASSOCIATION existiert nicht mehr; eine stehengebliebene Zeile ließe Doctrine
 *    beim Laden mit einem `ValueError` scheitern (Verwaltung → 500).
 * 2. `collaboration_interests`: `advisory_board` entfernen. Der Wert würde nur in
 *    der Verwaltung als roher Schlüssel erscheinen, wäre aber eine Zusage („Sitz im
 *    Beirat"), die es nicht mehr gibt. In PHP statt mit JSON-Funktionen, weil
 *    Produktion MariaDB fährt und lokal MySQL 8 läuft.
 * 3. `marketing_contact.origin`: `association` → `company` — sonst derselbe
 *    `ValueError` im Auftragsbuch. Bereits übertragene Kontakte gehen zurück auf
 *    `pending`, damit Brevo im nächsten Lauf `ORIGIN = COMPANY` bekommt — außer
 *    gesperrten (Widerruf): An die geht nichts mehr, sie bleiben auf `synced`.
 */
final class Version20261014120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merge association into company: rewrite organisation waitlist type, drop advisory_board interest, rewrite marketing origin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE organisation_waitlist_entry SET type = 'company' WHERE type = 'association'");

        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, collaboration_interests FROM organisation_waitlist_entry WHERE collaboration_interests LIKE '%advisory_board%'",
        );

        foreach ($rows as $row) {
            $interests = json_decode((string) $row['collaboration_interests'], true, flags: \JSON_THROW_ON_ERROR);
            $remaining = array_values(array_filter(
                \is_array($interests) ? $interests : [],
                static fn (mixed $value): bool => 'advisory_board' !== $value,
            ));

            $this->addSql(
                'UPDATE organisation_waitlist_entry SET collaboration_interests = ? WHERE id = ?',
                [json_encode($remaining, \JSON_THROW_ON_ERROR), $row['id']],
            );
        }

        // Eine Sperre bleibt auf `synced` (siehe MarketingSyncState) — auf `pending`
        // stünde sie in der Verwaltung dauerhaft als ausstehend. Die Bedingung bildet
        // `MarketingContact::isBlocked()` nach.
        $this->addSql("UPDATE marketing_contact
            SET origin = 'company',
                sync_state = CASE
                    WHEN sync_state = 'synced' AND (revoked_at IS NULL OR consent_at > revoked_at) THEN 'pending'
                    ELSE sync_state
                END
            WHERE origin = 'association'");
    }

    public function down(Schema $schema): void
    {
        // Welche Zeile vorher ein Verein war, steht nach `up()` nirgends mehr –
        // ein Rückbau müsste raten. Das Schema ist unverändert, es gibt also
        // auch nichts zurückzubauen.
        $this->throwIrreversibleMigrationException('Vereine und Unternehmen lassen sich nach dem Zusammenlegen nicht mehr unterscheiden.');
    }
}
