<?php

namespace App\Press;

/**
 * Eine Datei des Materialpakets — zugleich Vorschau auf der Seite und Eintrag im ZIP.
 *
 * ⚠ **Eine Liste für beides.** Die Vorschauen auf `/presse` und der Inhalt des
 * Pakets entstehen aus derselben Aufzählung; es gibt keine zweite Stelle, an der
 * jemand hätte vergessen können, eine Datei mitzunehmen. `PressPackageTest`
 * vergleicht das gepackte Ergebnis genau damit (AK-17).
 *
 * ⚠ `creditKey` ist Pflicht, sobald `kind` ein Porträt ist (AK-24).
 * `PressRegistryTest` erzwingt das — ein Bild ohne Urheberangabe ist nicht
 * freigabefähig, und eine Redaktion, die es trotzdem druckt, hat ein Problem,
 * das dieses Presse-Kit verursacht hat.
 *
 * ⚠ `publicPath` zeigt nicht zwingend nach `presse-kit/`: Das Gründerporträt liegt
 * seit jeher unter `uploads/team/` und ist mit 2048 × 1365 px drucktauglich
 * (rund 17 cm bei 300 dpi). Eine Kopie für die Presse wäre eine zweite Wahrheit,
 * die beim nächsten Bildwechsel auseinanderfällt.
 */
final readonly class PressAsset
{
    /**
     * @param string      $publicPath Pfad unterhalb von `public/`, ohne führenden Schrägstrich
     * @param string      $format     Dateiformat im Klartext, steht neben der Vorschau
     * @param bool        $onDark     Vorschaukachel dunkel hinterlegen — eine helle
     *                                Marke auf hellem Grund wäre unsichtbar
     * @param string|null $creditKey  Textschlüssel des Fotocredits, Domain `press`
     */
    public function __construct(
        public PressAssetKind $kind,
        public string $publicPath,
        public string $format,
        public bool $onDark = false,
        public ?string $creditKey = null,
    ) {
    }

    /** Textschlüssel der Variantenbezeichnung — trägt zugleich den Alternativtext. */
    public function labelKey(): string
    {
        return $this->kind->labelKey();
    }

    /** Dateiname ohne Verzeichnis — steht neben der Vorschau und im Paket. */
    public function fileName(): string
    {
        return basename($this->publicPath);
    }
}
