<?php

namespace App\Enum;

/**
 * Kostenstelle bzw. Einnahmequelle eines Finanzeintrags.
 *
 * Jede Kategorie gehört fest zu einer Richtung – `type()` ist die einzige
 * Quelle dieser Zuordnung. `FinanceEntry::setCategory()` leitet das Feld
 * `type` daraus ab, damit eine Ausgabe nicht versehentlich als Einnahme in der
 * Aggregation landet.
 *
 * OTHER_EXPENSE/OTHER_INCOME sind Sammelposten. Sie stehen nicht in der
 * ursprünglichen Anforderung, aber ohne sie müsste für jeden einmaligen Posten
 * (Porto, Standgebühr, Spendendose beim Workshop) eine neue Kategorie samt
 * Übersetzung in vier Sprachen angelegt werden – oder der Posten fehlte ganz,
 * was die veröffentlichte Summe falsch machte.
 */
enum FinanceCategory: string
{
    // Ausgaben
    case HOSTING = 'hosting';
    case EMAIL = 'email';
    case APPLE_DEVELOPER = 'apple_developer';
    case DOMAIN = 'domain';
    case INCLUSION_BOX_MATERIALS = 'inclusion_box_materials';
    case OTHER_EXPENSE = 'other_expense';

    // Einnahmen
    case MEMBERSHIP = 'membership';
    case PUBLIC_FUNDING = 'public_funding';
    case SPONSORSHIP = 'sponsorship';
    case DONATION = 'donation';
    case OTHER_INCOME = 'other_income';

    public function type(): FinanceType
    {
        return match ($this) {
            self::HOSTING,
            self::EMAIL,
            self::APPLE_DEVELOPER,
            self::DOMAIN,
            self::INCLUSION_BOX_MATERIALS,
            self::OTHER_EXPENSE => FinanceType::EXPENSE,
            self::MEMBERSHIP,
            self::PUBLIC_FUNDING,
            self::SPONSORSHIP,
            self::DONATION,
            self::OTHER_INCOME => FinanceType::INCOME,
        };
    }

    public function transKey(): string
    {
        return 'finance_category.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::HOSTING => 'Hosting',
            self::EMAIL => 'E-Mail-Versand',
            self::APPLE_DEVELOPER => 'Apple Developer Program',
            self::DOMAIN => 'Domain',
            self::INCLUSION_BOX_MATERIALS => 'Material Inclusion Box',
            self::OTHER_EXPENSE => 'Sonstige Ausgabe',
            self::MEMBERSHIP => 'Partner-Mitgliedschaft',
            self::PUBLIC_FUNDING => 'Öffentliche Förderung',
            self::SPONSORSHIP => 'Sponsoring',
            self::DONATION => 'Spende',
            self::OTHER_INCOME => 'Sonstige Einnahme',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::HOSTING => '🖥️',
            self::EMAIL => '✉️',
            self::APPLE_DEVELOPER => '📱',
            self::DOMAIN => '🌐',
            self::INCLUSION_BOX_MATERIALS => '📦',
            self::OTHER_EXPENSE => '🧾',
            self::MEMBERSHIP => '🤝',
            self::PUBLIC_FUNDING => '🏛️',
            self::SPONSORSHIP => '🏢',
            self::DONATION => '💚',
            self::OTHER_INCOME => '💶',
        };
    }

    /**
     * Nur diese Kategorie trägt eine Stückzahl (gelieferte Inclusion Boxes).
     * Die Zahl hängt am Beleg, der die Boxen bezahlt hat – so kann sie nicht
     * getrennt von den Kosten veralten.
     */
    public function tracksQuantity(): bool
    {
        return self::INCLUSION_BOX_MATERIALS === $this;
    }

    /**
     * @return list<self>
     */
    public static function casesFor(FinanceType $type): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $category) => $category->type() === $type,
        ));
    }
}
