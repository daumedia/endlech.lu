<?php

namespace App\Open;

use App\Enum\Canton;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Ordnet den Freitext aus `Restaurant::$city` einer Luxemburger Gemeinde und
 * damit einem Kanton zu.
 *
 * Warum überhaupt eine Tabelle: `city` ist ein Eingabefeld ohne Auswahlliste.
 * Dort steht mal die Gemeinde ("Strassen"), mal eine Ortschaft darin
 * ("Belval"), mal ein Stadtteil ("Bonnevoie"), mal die luxemburgische
 * Schreibweise ("Lëtzebuerg"). Eine reine GROUP-BY-Auswertung über `city`
 * zählte diese Fälle als verschiedene Orte und machte jede Abdeckungsquote
 * falsch.
 *
 * Was hier NICHT passiert: Raten. Ein unbekannter Wert bleibt unzugeordnet und
 * wird auf der Seite als solcher ausgewiesen. Eine erfundene Zuordnung wäre auf
 * einer Transparenzseite schlimmer als eine sichtbare Lücke.
 *
 * Stand der Gebietsgliederung: nach den Fusionen vom 1. Januar 2024
 * (Bous-Waldbredimus, Groussbus-Wal) – 100 Gemeinden in 12 Kantonen.
 */
final class CantonResolver
{
    /**
     * Alle 100 Gemeinden, gruppiert nach Kanton, in amtlicher französischer
     * Schreibweise. Der Suchschlüssel wird daraus zur Laufzeit erzeugt – so
     * gibt es keine zweite, separat zu pflegende Slug-Spalte.
     *
     * @var array<string, list<string>>
     */
    private const COMMUNES_BY_CANTON = [
        'capellen' => [
            'Bertrange', 'Dippach', 'Garnich', 'Habscht', 'Käerjeng',
            'Kehlen', 'Koerich', 'Kopstal', 'Mamer', 'Steinfort',
        ],
        'clervaux' => [
            'Clervaux', 'Parc Hosingen', 'Troisvierges', 'Weiswampach', 'Wincrange',
        ],
        'diekirch' => [
            'Bettendorf', 'Bourscheid', 'Diekirch', 'Erpeldange-sur-Sûre', 'Ettelbruck',
            'Feulen', 'Mertzig', 'Reisdorf', 'Schieren', "Vallée de l'Ernz",
        ],
        'echternach' => [
            'Beaufort', 'Bech', 'Berdorf', 'Consdorf', 'Echternach',
            'Rosport-Mompach', 'Waldbillig',
        ],
        'esch_sur_alzette' => [
            'Bettembourg', 'Differdange', 'Dudelange', 'Esch-sur-Alzette', 'Frisange',
            'Kayl', 'Leudelange', 'Mondercange', 'Pétange', 'Reckange-sur-Mess',
            'Roeser', 'Rumelange', 'Sanem', 'Schifflange',
        ],
        'grevenmacher' => [
            'Betzdorf', 'Biwer', 'Flaxweiler', 'Grevenmacher', 'Junglinster',
            'Manternach', 'Mertert', 'Wormeldange',
        ],
        'luxembourg' => [
            'Contern', 'Hesperange', 'Luxembourg', 'Niederanven', 'Sandweiler',
            'Schuttrange', 'Steinsel', 'Strassen', 'Walferdange', 'Weiler-la-Tour',
        ],
        'mersch' => [
            'Bissen', 'Colmar-Berg', 'Fischbach', 'Heffingen', 'Helperknapp',
            'Larochette', 'Lintgen', 'Lorentzweiler', 'Mersch', 'Nommern',
        ],
        'redange' => [
            'Beckerich', 'Ell', 'Groussbus-Wal', 'Préizerdaul', 'Rambrouch',
            'Redange', 'Saeul', 'Useldange', 'Vichten',
        ],
        'remich' => [
            'Bous-Waldbredimus', 'Dalheim', 'Lenningen', 'Mondorf-les-Bains',
            'Remich', 'Schengen', 'Stadtbredimus',
        ],
        'vianden' => [
            'Putscheid', 'Tandel', 'Vianden',
        ],
        'wiltz' => [
            'Boulaide', 'Esch-sur-Sûre', 'Goesdorf', 'Kiischpelt',
            'Lac de la Haute-Sûre', 'Wiltz', 'Winseler',
        ],
    ];

    /**
     * Schreibweisen und Ortschaften, die auf eine Gemeinde zeigen.
     *
     * Schlüssel ist der normalisierte Eingabewert, Wert der amtliche
     * Gemeindename aus COMMUNES_BY_CANTON. Abgedeckt sind die Stadtteile der
     * Stadt Luxemburg (dort steht praktisch nie "Luxembourg" im Formular), die
     * luxemburgischen und deutschen Namen der größeren Gemeinden sowie die
     * Ortschaften, die bekannter sind als ihre Gemeinde (Belval, Howald,
     * Findel, Belvaux).
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        // Stadt Luxemburg – Stadtteile und Schreibweisen
        'letzebuerg' => 'Luxembourg',
        'luxemburg' => 'Luxembourg',
        'luxembourg-ville' => 'Luxembourg',
        'stad' => 'Luxembourg',
        'belair' => 'Luxembourg',
        'beggen' => 'Luxembourg',
        'bonnevoie' => 'Luxembourg',
        'cents' => 'Luxembourg',
        'cloche-d-or' => 'Luxembourg',
        'cessange' => 'Luxembourg',
        'clausen' => 'Luxembourg',
        'dommeldange' => 'Luxembourg',
        'eich' => 'Luxembourg',
        'gare' => 'Luxembourg',
        'gasperich' => 'Luxembourg',
        'grund' => 'Luxembourg',
        'hamm' => 'Luxembourg',
        'hollerich' => 'Luxembourg',
        'kirchberg' => 'Luxembourg',
        'limpertsberg' => 'Luxembourg',
        'merl' => 'Luxembourg',
        'muhlenbach' => 'Luxembourg',
        'neudorf' => 'Luxembourg',
        'pfaffenthal' => 'Luxembourg',
        'pulvermuhl' => 'Luxembourg',
        'rollingergrund' => 'Luxembourg',
        'weimerskirch' => 'Luxembourg',

        // Süden
        'esch' => 'Esch-sur-Alzette',
        'esch-alzette' => 'Esch-sur-Alzette',
        'esch-uelzecht' => 'Esch-sur-Alzette',
        'belval' => 'Sanem',
        'belvaux' => 'Sanem',
        'suessem' => 'Sanem',
        'soleuvre' => 'Sanem',
        'diddeleng' => 'Dudelange',
        'dudelingen' => 'Dudelange',
        'deifferdeng' => 'Differdange',
        'differdingen' => 'Differdange',
        'peiteng' => 'Pétange',
        'petingen' => 'Pétange',
        'rodange' => 'Pétange',
        'lamadelaine' => 'Pétange',
        'beetebuerg' => 'Bettembourg',
        'bettemburg' => 'Bettembourg',
        'schiffleng' => 'Schifflange',
        'schifflingen' => 'Schifflange',
        'remeleng' => 'Rumelange',
        'rumelingen' => 'Rumelange',
        'kayl-tetange' => 'Kayl',
        'tetange' => 'Kayl',
        'noertzange' => 'Bettembourg',
        'bergem' => 'Mondercange',
        'foetz' => 'Mondercange',
        'crauthem' => 'Roeser',

        // Zentrum
        'howald' => 'Hesperange',
        'hesper' => 'Hesperange',
        'alzingen' => 'Hesperange',
        'stroossen' => 'Strassen',
        'bartreng' => 'Bertrange',
        'bartringen' => 'Bertrange',
        'findel' => 'Niederanven',
        'senningerberg' => 'Niederanven',
        'nidderaanwen' => 'Niederanven',
        'oberanven' => 'Niederanven',
        'munsbach' => 'Schuttrange',
        'bereldange' => 'Walferdange',
        'helmsange' => 'Walferdange',
        'walfer' => 'Walferdange',
        'mamer-cap' => 'Mamer',
        'capellen' => 'Mamer',
        'olm' => 'Kehlen',
        'bridel' => 'Kopstal',
        'moutfort' => 'Contern',
        'sandweiler-contern' => 'Sandweiler',

        // Osten und Mosel
        'greivemaacher' => 'Grevenmacher',
        'reimech' => 'Remich',
        'munneref' => 'Mondorf-les-Bains',
        'mondorf' => 'Mondorf-les-Bains',
        'bad-mondorf' => 'Mondorf-les-Bains',
        'iechternach' => 'Echternach',
        'wasserbillig' => 'Mertert',
        'rosport' => 'Rosport-Mompach',
        'mompach' => 'Rosport-Mompach',
        'remerschen' => 'Schengen',
        'wellenstein' => 'Schengen',
        'bous' => 'Bous-Waldbredimus',
        'waldbredimus' => 'Bous-Waldbredimus',
        'gostingen' => 'Flaxweiler',
        'ehnen' => 'Wormeldange',

        // Norden und Westen
        'miersch' => 'Mersch',
        'ettelbreck' => 'Ettelbruck',
        'ettelbruck-warken' => 'Ettelbruck',
        'diekrech' => 'Diekirch',
        'clierf' => 'Clervaux',
        'klierf' => 'Clervaux',
        'wolz' => 'Wiltz',
        'veianen' => 'Vianden',
        'reiden' => 'Redange',
        'redange-sur-attert' => 'Redange',
        'hosingen' => 'Parc Hosingen',
        'grosbous' => 'Groussbus-Wal',
        'wahl' => 'Groussbus-Wal',
        'boevange-sur-attert' => 'Helperknapp',
        'tuntange' => 'Helperknapp',
        'hobscheid' => 'Habscht',
        'septfontaines' => 'Habscht',
        'bascharage' => 'Käerjeng',
        'clemency' => 'Käerjeng',
        'insenborn' => 'Lac de la Haute-Sûre',
        'esch-sauer' => 'Esch-sur-Sûre',
        'heiderscheid' => 'Esch-sur-Sûre',
        'erpeldange' => 'Erpeldange-sur-Sûre',
        'medernach' => "Vallée de l'Ernz",
        'ermsdorf' => "Vallée de l'Ernz",
        'wilwerwiltz' => 'Kiischpelt',
        'bigonville' => 'Rambrouch',
    ];

    /**
     * Zwei getrennte Indizes, jeweils normalisierter Name → [amtlicher Name,
     * Kanton]. Die Trennung ist kein Zierrat: Beim Zerlegen zusammengesetzter
     * Angaben ("Rue de la Gare, Strassen") dürfen nur echte Gemeindenamen
     * greifen. Läge "gare" als Stadtteil-Alias im selben Topf, landete der
     * Eintrag in Luxemburg statt in Strassen.
     *
     * @var array<string, array{0: string, 1: Canton}>|null
     */
    private static ?array $communeIndex = null;

    /** @var array<string, array{0: string, 1: Canton}>|null */
    private static ?array $aliasIndex = null;

    private readonly AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    /**
     * Amtlicher Gemeindename zu einem beliebigen Ortseintrag, oder null,
     * wenn der Wert keiner Luxemburger Gemeinde zugeordnet werden kann.
     */
    public function resolveCommune(string $city): ?string
    {
        return $this->lookup($city)[0] ?? null;
    }

    public function resolveCanton(string $city): ?Canton
    {
        return $this->lookup($city)[1] ?? null;
    }

    /**
     * @return list<Canton>
     */
    public function cantons(): array
    {
        return Canton::cases();
    }

    /**
     * Nenner der landesweiten Abdeckungsquote: 100 Gemeinden. Zählt bewusst
     * den Gemeindeindex, nicht die Aliase – sonst wäre der Nenner die Anzahl
     * bekannter Schreibweisen.
     */
    public function totalCommunes(): int
    {
        return \count(self::communeIndex());
    }

    /**
     * @return array{0: string, 1: Canton}|null
     */
    private function lookup(string $city): ?array
    {
        $key = $this->normalize($city);

        if ('' === $key) {
            return null;
        }

        $communes = self::communeIndex();
        $aliases = self::aliasIndex();

        if (isset($communes[$key])) {
            return $communes[$key];
        }

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        // Zusammengesetzte Angaben wie "Luxembourg-Grund" oder "1 rue de
        // Strassen": erst nach einer echten Gemeinde in den Teilstücken
        // suchen, danach erst nach einem Alias.
        $parts = explode('-', $key);

        foreach ($parts as $part) {
            if (isset($communes[$part])) {
                return $communes[$part];
            }
        }

        foreach ($parts as $part) {
            if (isset($aliases[$part])) {
                return $aliases[$part];
            }
        }

        return null;
    }

    /**
     * Erzeugt den Vergleichsschlüssel: kleingeschrieben, ohne Akzente,
     * Trennzeichen vereinheitlicht. "Esch/Alzette", "Esch sur Alzette" und
     * "Esch-sur-Alzette" landen dadurch auf demselben Wert.
     */
    private function normalize(string $city): string
    {
        return $this->slugger->slug(trim($city), '-')->lower()->toString();
    }

    /**
     * @return array<string, array{0: string, 1: Canton}>
     */
    private static function communeIndex(): array
    {
        if (null !== self::$communeIndex) {
            return self::$communeIndex;
        }

        $slugger = new AsciiSlugger();
        $index = [];

        foreach (self::COMMUNES_BY_CANTON as $cantonValue => $communes) {
            $canton = Canton::from($cantonValue);

            foreach ($communes as $commune) {
                $index[$slugger->slug($commune, '-')->lower()->toString()] = [$commune, $canton];
            }
        }

        return self::$communeIndex = $index;
    }

    /**
     * @return array<string, array{0: string, 1: Canton}>
     */
    private static function aliasIndex(): array
    {
        if (null !== self::$aliasIndex) {
            return self::$aliasIndex;
        }

        $slugger = new AsciiSlugger();
        $communes = self::communeIndex();
        $index = [];

        foreach (self::ALIASES as $alias => $commune) {
            $key = $slugger->slug($alias, '-')->lower()->toString();

            // Ein Alias darf eine echte Gemeinde nie verdecken: "Bous" gehört
            // heute zu Bous-Waldbredimus, "Kayl" ist dagegen eine eigene
            // Gemeinde und muss als solche gewinnen.
            if (isset($communes[$key])) {
                continue;
            }

            $target = $slugger->slug($commune, '-')->lower()->toString();

            if (isset($communes[$target])) {
                $index[$key] = $communes[$target];
            }
        }

        return self::$aliasIndex = $index;
    }
}
