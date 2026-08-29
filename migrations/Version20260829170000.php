<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * BF-89 · `self_confirmed_at` trennt den eingelösten Double-Opt-In vom
 * Verwaltungs-Backfill.
 *
 * ⚠ **Warum ein zweites Feld und nicht eine weitere Fallunterscheidung.**
 * `confirmed_at` wird an zwei Stellen gesetzt: beim Klick auf den
 * Bestätigungslink **und** beim Statuswechsel in der Verwaltung, wenn ein
 * Eintrag von Hand weitergesetzt wird. Damit trägt eine Spalte zwei
 * Bedeutungen — und jede Prüfung, die „ist bestätigt" fragt, bekommt eine
 * Antwort, die beides meinen kann.
 *
 * Der erste Reparaturversuch (BF-83) zog die Prüfung **vor** den Backfill.
 * Das machte den ersten Statuswechsel sauber und den zweiten wieder falsch:
 * Er fand das nachgesetzte Feld vor. Eine Reparatur an der Reihenfolge kann
 * eine Zweideutigkeit nicht auflösen, sie verschiebt sie nur.
 *
 * ⚠ **Zum Bestand:** Die Datenmigration setzt `self_confirmed_at =
 * confirmed_at` für alle bereits bestätigten Einträge. Das ist eine
 * **Annahme** — unter ihnen können Einträge sein, deren `confirmed_at` aus
 * einem Verwaltungs-Statuswechsel stammt. Sie ist vertretbar, weil zum
 * Zeitpunkt dieser Migration **kein einziger Eintrag eine Werbe-Einwilligung
 * trägt** (Feature 04 ist nicht in Betrieb, `marketing_consent_at` ist
 * überall `NULL`). Die Unterscheidung wirkt damit ausschließlich für
 * Bestätigungen ab jetzt — und dort ist sie eindeutig.
 *
 * MariaDB-10.5-tauglich: zwei reine `ADD COLUMN` auf nullable plus zwei
 * `UPDATE` ohne besondere Syntax.
 */
final class Version20260829170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'BF-89: self_confirmed_at an beiden Wartelisten — Double-Opt-In vom Admin-Backfill getrennt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner_waitlist_entry ADD self_confirmed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE organisation_waitlist_entry ADD self_confirmed_at DATETIME DEFAULT NULL');

        // Bestand: siehe Annahme im Klassenkommentar.
        $this->addSql('UPDATE partner_waitlist_entry SET self_confirmed_at = confirmed_at WHERE confirmed_at IS NOT NULL');
        $this->addSql('UPDATE organisation_waitlist_entry SET self_confirmed_at = confirmed_at WHERE confirmed_at IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner_waitlist_entry DROP self_confirmed_at');
        $this->addSql('ALTER TABLE organisation_waitlist_entry DROP self_confirmed_at');
    }
}
