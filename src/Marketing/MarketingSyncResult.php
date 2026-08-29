<?php

namespace App\Marketing;

/**
 * Ergebnis eines Sync-Laufs – Zahlen für den Konsolenbefehl und das Protokoll.
 *
 * ⚠ Trägt **keine** E-Mail-Adressen. Das Ergebnis landet in der Konsolenausgabe
 * und im Protokoll; eine vollständige Adresse hat dort nichts zu suchen
 * (AK-31).
 */
final readonly class MarketingSyncResult
{
    public function __construct(
        /** Erfolgreich angelegt oder fortgeschrieben. */
        public int $synced = 0,
        /** Erfolgreich bei Brevo entfernt und lokal abgeräumt. */
        public int $removed = 0,
        /** Fehlversuche in diesem Lauf. */
        public int $failed = 0,
        /** Übersprungen, weil gesperrt. */
        public int $skipped = 0,
        /** Lief der Dienst überhaupt, oder fehlt der Schlüssel? */
        public bool $configured = true,
    ) {
    }

    public function total(): int
    {
        return $this->synced + $this->removed + $this->failed + $this->skipped;
    }

    public function hasWork(): bool
    {
        return $this->total() > 0;
    }
}
