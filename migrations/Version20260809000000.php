<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert accessibility, dietary and payment flags on restaurant_suggestion from boolean to tri-state (yes/no/unknown)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant_suggestion
            CHANGE is_wheelchair_accessible is_wheelchair_accessible VARCHAR(10) DEFAULT NULL,
            CHANGE has_accessible_toilet has_accessible_toilet VARCHAR(10) DEFAULT NULL,
            CHANGE allows_assistance_dogs allows_assistance_dogs VARCHAR(10) DEFAULT NULL,
            CHANGE has_bright_lighting has_bright_lighting VARCHAR(10) DEFAULT NULL,
            CHANGE has_changing_table has_changing_table VARCHAR(10) DEFAULT NULL,
            CHANGE has_disabled_parking has_disabled_parking VARCHAR(10) DEFAULT NULL,
            CHANGE accepts_cash accepts_cash VARCHAR(10) DEFAULT NULL,
            CHANGE accepts_card accepts_card VARCHAR(10) DEFAULT NULL,
            CHANGE accepts_payconiq accepts_payconiq VARCHAR(10) DEFAULT NULL,
            CHANGE is_vegan is_vegan VARCHAR(10) DEFAULT NULL,
            CHANGE is_vegetarian is_vegetarian VARCHAR(10) DEFAULT NULL,
            CHANGE is_halal is_halal VARCHAR(10) DEFAULT NULL');

        // Ein leeres Häkchen bedeutete bisher "nicht angekreuzt" – also unbekannt,
        // nicht "nein" (das Admin-UI zeigte es als "Nein / unbekannt").
        $this->addSql("UPDATE restaurant_suggestion SET
            is_wheelchair_accessible = CASE WHEN is_wheelchair_accessible = '1' THEN 'yes' ELSE 'unknown' END,
            has_accessible_toilet = CASE WHEN has_accessible_toilet = '1' THEN 'yes' ELSE 'unknown' END,
            allows_assistance_dogs = CASE WHEN allows_assistance_dogs = '1' THEN 'yes' ELSE 'unknown' END,
            has_bright_lighting = CASE WHEN has_bright_lighting = '1' THEN 'yes' ELSE 'unknown' END,
            has_changing_table = CASE WHEN has_changing_table = '1' THEN 'yes' ELSE 'unknown' END,
            has_disabled_parking = CASE WHEN has_disabled_parking = '1' THEN 'yes' ELSE 'unknown' END,
            accepts_cash = CASE WHEN accepts_cash = '1' THEN 'yes' ELSE 'unknown' END,
            accepts_card = CASE WHEN accepts_card = '1' THEN 'yes' ELSE 'unknown' END,
            accepts_payconiq = CASE WHEN accepts_payconiq = '1' THEN 'yes' ELSE 'unknown' END,
            is_vegan = CASE WHEN is_vegan = '1' THEN 'yes' ELSE 'unknown' END,
            is_vegetarian = CASE WHEN is_vegetarian = '1' THEN 'yes' ELSE 'unknown' END,
            is_halal = CASE WHEN is_halal = '1' THEN 'yes' ELSE 'unknown' END");
    }

    public function down(Schema $schema): void
    {
        // 'no' und 'unknown' (und NULL) fallen gleichermaßen auf 0 zurück – die
        // Unterscheidung geht beim Rückbau zwangsläufig verloren.
        $this->addSql("UPDATE restaurant_suggestion SET
            is_wheelchair_accessible = CASE WHEN is_wheelchair_accessible = 'yes' THEN '1' ELSE '0' END,
            has_accessible_toilet = CASE WHEN has_accessible_toilet = 'yes' THEN '1' ELSE '0' END,
            allows_assistance_dogs = CASE WHEN allows_assistance_dogs = 'yes' THEN '1' ELSE '0' END,
            has_bright_lighting = CASE WHEN has_bright_lighting = 'yes' THEN '1' ELSE '0' END,
            has_changing_table = CASE WHEN has_changing_table = 'yes' THEN '1' ELSE '0' END,
            has_disabled_parking = CASE WHEN has_disabled_parking = 'yes' THEN '1' ELSE '0' END,
            accepts_cash = CASE WHEN accepts_cash = 'yes' THEN '1' ELSE '0' END,
            accepts_card = CASE WHEN accepts_card = 'yes' THEN '1' ELSE '0' END,
            accepts_payconiq = CASE WHEN accepts_payconiq = 'yes' THEN '1' ELSE '0' END,
            is_vegan = CASE WHEN is_vegan = 'yes' THEN '1' ELSE '0' END,
            is_vegetarian = CASE WHEN is_vegetarian = 'yes' THEN '1' ELSE '0' END,
            is_halal = CASE WHEN is_halal = 'yes' THEN '1' ELSE '0' END");

        $this->addSql('ALTER TABLE restaurant_suggestion
            CHANGE is_wheelchair_accessible is_wheelchair_accessible TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE has_accessible_toilet has_accessible_toilet TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE allows_assistance_dogs allows_assistance_dogs TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE has_bright_lighting has_bright_lighting TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE has_changing_table has_changing_table TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE has_disabled_parking has_disabled_parking TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE accepts_cash accepts_cash TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE accepts_card accepts_card TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE accepts_payconiq accepts_payconiq TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE is_vegan is_vegan TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE is_vegetarian is_vegetarian TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE is_halal is_halal TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
