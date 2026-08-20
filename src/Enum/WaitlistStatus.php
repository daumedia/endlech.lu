<?php

namespace App\Enum;

/**
 * Bearbeitungsstand einer Wartelisten-Anmeldung – gemeinsam für Partner-
 * (Restaurants) und Organisations-Anmeldungen.
 *
 * PENDING → CONFIRMED läuft über den Double-Opt-In-Link in der E-Mail und ist
 * der einzige Übergang, den der Interessent selbst auslöst. Alles danach
 * pflegt das Team im Admin.
 *
 * QUALIFIED sitzt bewusst zwischen CONTACTED und CONVERTED: Bei Gemeinden und
 * Unternehmen liegt zwischen Erstkontakt und Abschluss regelmäßig eine
 * Vorprüfung (Budget, Zuständigkeit, Ausschlusskriterien beim Sponsoring).
 */
enum WaitlistStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case CONVERTED = 'converted';
    case DECLINED = 'declined';

    public function transKey(): string
    {
        return 'waitlist_status.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Unbestätigt',
            self::CONFIRMED => 'Bestätigt',
            self::CONTACTED => 'Kontaktiert',
            self::QUALIFIED => 'Qualifiziert',
            self::CONVERTED => 'Gewonnen',
            self::DECLINED => 'Abgelehnt',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::PENDING => '⏳',
            self::CONFIRMED => '✅',
            self::CONTACTED => '📞',
            self::QUALIFIED => '🔎',
            self::CONVERTED => '🤝',
            self::DECLINED => '❌',
        };
    }

    /**
     * Tailwind-Klassen für das Status-Badge im Admin.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-100 text-amber-800',
            self::CONFIRMED => 'bg-blue-100 text-blue-700',
            self::CONTACTED => 'bg-purple-100 text-purple-700',
            self::QUALIFIED => 'bg-cyan-100 text-cyan-800',
            self::CONVERTED => 'bg-green-100 text-green-700',
            self::DECLINED => 'bg-red-100 text-red-600',
        };
    }
}
