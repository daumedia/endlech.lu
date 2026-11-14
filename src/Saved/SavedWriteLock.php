<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\User;
use Symfony\Component\Lock\LockFactory;

/**
 * Eine Schreibsperre je Konto für alles, was Feature 17 speichert (`design.md`,
 * Missbrauchsschutz; Muster `AvatarUploadService`).
 *
 * Jeder schreibende Weg prüft **und** schreibt unter dieser Sperre. Doppelklick, zwei Tabs,
 * zwei Geräte laufen damit nacheinander statt gleichzeitig: Es entstehen weder doppelte
 * Einträge (AK-05, AK-31) noch eine 26. Liste oder zwei gleichnamige (EC-05). Die eindeutigen
 * Indizes bleiben die letzte Instanz.
 *
 * ⚠ **Warum nicht einfach die Unique-Verletzung abfangen?** Eine Ausnahme beim `flush()`
 * schließt Doctrines EntityManager; danach scheitert jede weitere Arbeit derselben Anfrage.
 * Und die 25er-Grenze kann kein Index prüfen.
 *
 * ⚠ Speicher ist `LOCK_DSN=flock` — wirkt, solange die Anwendung in **einem** Container läuft
 * (Stand 2026-11). Bei mehreren bräuchte es einen gemeinsamen Sperrspeicher.
 */
final readonly class SavedWriteLock
{
    /** Länger hält keine Schreibarbeit dieses Features; danach gibt der Speicher frei. */
    private const float TTL_SECONDS = 30.0;

    public function __construct(private LockFactory $locks)
    {
    }

    /**
     * @template T
     *
     * @param callable(): T $arbeit
     *
     * @return T
     */
    public function forAccount(User $user, callable $arbeit): mixed
    {
        $lock = $this->locks->createLock('gespeichert-'.$user->getId(), self::TTL_SECONDS);
        $lock->acquire(true);

        try {
            return $arbeit();
        } finally {
            $lock->release();
        }
    }
}
