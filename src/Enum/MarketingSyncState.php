<?php

namespace App\Enum;

/**
 * Stand einer Zeile im Auftragsbuch `marketing_contact` (Feature 04).
 *
 * Der Zustand sagt, was Brevo noch schuldet – nicht, was der Interessent
 * getan hat. Deshalb gibt es kein „widerrufen": Ein Widerruf ist entweder eine
 * Sperre (`revoked_at` gesetzt, Zustand bleibt `synced`) oder ein Löschauftrag
 * (`REMOVAL_PENDING`).
 *
 * REMOVAL_PENDING muss die Löschung seiner Quelle überleben: Der
 * Wartelisten-Widerruf entfernt den Eintrag, und ein Auftrag, der an ihm hinge,
 * verschwände mit ihm – der Kontakt bliebe für immer in Brevo. Genau dafür hat
 * die Tabelle keinen Fremdschlüssel.
 */
enum MarketingSyncState: string
{
    case PENDING = 'pending';
    case SYNCED = 'synced';
    case FAILED = 'failed';
    case REMOVAL_PENDING = 'removal_pending';

    /**
     * Sperre an Brevo übertragen (Feature 12, AK-36).
     *
     * Entsteht, wenn jemand im Profil oder in der Einführung abbestellt und der Kontakt schon
     * einmal übertragen war. Gesperrt wird statt gelöscht: Ein gelöschter und später neu
     * angelegter Kontakt wäre für Brevo ein neuer, und die Willkommens-Mail käme ein zweites
     * Mal (AK-44). Brevos eigene Abmeldeerinnerung bleibt so ebenfalls erhalten.
     *
     * Wie `REMOVAL_PENDING` überlebt der Zustand einen Fehlversuch — sonst wäre der Auftrag
     * nach dem ersten 429 weg und der Kontakt bekäme weiter Werbung.
     */
    case BLACKLIST_PENDING = 'blacklist_pending';

    public function transKey(): string
    {
        return 'marketing_sync_state.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::SYNCED => 'Übertragen',
            self::FAILED => 'Fehlgeschlagen',
            self::REMOVAL_PENDING => 'Löschung ausstehend',
            self::BLACKLIST_PENDING => 'Sperre ausstehend',
        };
    }

    /**
     * Tailwind-Klassen für das Abzeichen im Admin.
     *
     * Liefert **nur Farbe** – Form und Größe bleiben im Template, wie bei
     * `WaitlistStatus`. Das Abzeichen trägt zusätzlich Text: Farbe trägt hier
     * nie allein (docs/design-system.md).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-100 text-amber-800',
            self::SYNCED => 'bg-green-100 text-green-700',
            self::FAILED => 'bg-red-100 text-red-600',
            self::REMOVAL_PENDING => 'bg-gray-100 text-gray-700',
            self::BLACKLIST_PENDING => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Wartet diese Zeile auf einen Aufruf an Brevo?
     *
     * ⚠ **`FAILED` gehört dazu** — ein Fehlversuch ist kein Endzustand,
     * sondern ein Zwischenstand (AK-19). Ob eine Zeile endgültig liegen
     * bleibt, entscheidet allein ihr Versuchszähler
     * (`MarketingContact::hasExhaustedAttempts()`), nicht dieser Zustand.
     *
     * ⚠ Bis BF-86 stand hier das Gegenteil: `FAILED` war ausgenommen, und der
     * Kommentar behauptete, der Lauf greife solche Zeilen „über ihren
     * Versuchszähler" wieder auf. Das tat er nicht — die Abfrage in
     * `MarketingContactRepository::findOpenForSync()` filterte auf den
     * Zustand. Ein einzelner 429 fror den Kontakt dauerhaft ein. Wer diese
     * Methode ändert, ändert `findOpenForSync()` mit.
     */
    public function isOpen(): bool
    {
        return self::PENDING === $this
            || self::REMOVAL_PENDING === $this
            || self::BLACKLIST_PENDING === $this
            || self::FAILED === $this;
    }
}
