<?php

namespace App\Enum;

/**
 * Herkunft eines Marketing-Kontakts – die **Rolle im Vertrieb**, aus der die
 * Adresse stammt.
 *
 * ⚠ Dieses Merkmal sagt nichts über die Person aus. Es bezeichnet, in welcher
 * Rolle jemand mit Endlech.lu zu tun hat (Feature 04, AK-30) – und ausdrücklich
 * **nicht**, ob jemand selbst von einer Behinderung betroffen ist. Auf einer
 * Barrierefreiheitsplattform ist das der Unterschied zwischen einem
 * Vertriebsmerkmal und einer besonderen Kategorie nach Art. 9 DSGVO.
 *
 * Die Werte decken sich absichtlich mit `OrganisationType` (commune, company,
 * association) und ergänzen sie um die Quellen, die dort keine Entsprechung
 * haben: die Partner-Warteliste, das Nutzerkonto und die App-Warteliste.
 */
enum MarketingOrigin: string
{
    case PARTNER = 'partner';
    case COMMUNE = 'commune';
    case COMPANY = 'company';
    case ASSOCIATION = 'association';
    case ACCOUNT = 'account';
    case APP = 'app';

    public function transKey(): string
    {
        return 'marketing_origin.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::PARTNER => 'Partner',
            self::COMMUNE => 'Gemeinde',
            self::COMPANY => 'Unternehmen',
            self::ASSOCIATION => 'Verein',
            self::ACCOUNT => 'Nutzerkonto',
            self::APP => 'App-Warteliste',
        };
    }

    /**
     * Wert des Brevo-Attributs `ORIGIN`.
     *
     * Großgeschrieben, weil er in Brevo als Segmentbedingung von Hand
     * ausgewählt wird und dort neben den Attributnamen steht.
     */
    public function brevoValue(): string
    {
        return strtoupper($this->value);
    }

    /**
     * Herkunft aus einem Organisationstyp ableiten.
     *
     * Die drei Organisationstypen tragen dieselben Werte; die Zuordnung steht
     * hier trotzdem ausgeschrieben, damit ein späterer neuer Typ hier einen
     * Fehler erzeugt statt still durchzufallen.
     */
    public static function fromOrganisationType(OrganisationType $type): self
    {
        return match ($type) {
            OrganisationType::COMMUNE => self::COMMUNE,
            OrganisationType::COMPANY => self::COMPANY,
            OrganisationType::ASSOCIATION => self::ASSOCIATION,
        };
    }
}
