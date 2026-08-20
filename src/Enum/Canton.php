<?php

namespace App\Enum;

/**
 * Die zwölf Kantone Luxemburgs.
 *
 * Sie sind reine Verwaltungsgliederung ohne eigene Behörde, taugen aber gut
 * als Aggregationsebene: 100 Gemeinden ergeben auf der Open-Startup-Seite eine
 * unlesbare Liste, zwölf Kantone eine Karte im Kopf.
 */
enum Canton: string
{
    case CAPELLEN = 'capellen';
    case CLERVAUX = 'clervaux';
    case DIEKIRCH = 'diekirch';
    case ECHTERNACH = 'echternach';
    case ESCH_SUR_ALZETTE = 'esch_sur_alzette';
    case GREVENMACHER = 'grevenmacher';
    case LUXEMBOURG = 'luxembourg';
    case MERSCH = 'mersch';
    case REDANGE = 'redange';
    case REMICH = 'remich';
    case VIANDEN = 'vianden';
    case WILTZ = 'wiltz';

    /**
     * Amtlicher französischer Name – auf der Seite die neutrale Schreibweise,
     * unabhängig von der gewählten Oberflächensprache.
     */
    public function label(): string
    {
        return match ($this) {
            self::CAPELLEN => 'Capellen',
            self::CLERVAUX => 'Clervaux',
            self::DIEKIRCH => 'Diekirch',
            self::ECHTERNACH => 'Echternach',
            self::ESCH_SUR_ALZETTE => 'Esch-sur-Alzette',
            self::GREVENMACHER => 'Grevenmacher',
            self::LUXEMBOURG => 'Luxembourg',
            self::MERSCH => 'Mersch',
            self::REDANGE => 'Redange',
            self::REMICH => 'Remich',
            self::VIANDEN => 'Vianden',
            self::WILTZ => 'Wiltz',
        };
    }

    /**
     * Anzahl der Gemeinden im Kanton – Nenner der Abdeckungsquote.
     * Stand: nach den Fusionen vom 1. Januar 2024 (100 Gemeinden landesweit).
     */
    public function communeCount(): int
    {
        return match ($this) {
            self::CAPELLEN => 10,
            self::CLERVAUX => 5,
            self::DIEKIRCH => 10,
            self::ECHTERNACH => 7,
            self::ESCH_SUR_ALZETTE => 14,
            self::GREVENMACHER => 8,
            self::LUXEMBOURG => 10,
            self::MERSCH => 10,
            self::REDANGE => 9,
            self::REMICH => 7,
            self::VIANDEN => 3,
            self::WILTZ => 7,
        };
    }
}
