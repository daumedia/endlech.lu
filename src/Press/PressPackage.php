<?php

namespace App\Press;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Das Materialpaket als Datei — Pfad, Vorhandensein, Größe.
 *
 * ⚠ **Die Größe wird bei jedem Aufruf von der Platte gelesen und steht nicht im
 * Übersetzungskatalog.** Eine Zahl im Katalog veraltet still und sagt nichts
 * darüber, ob die Datei überhaupt da ist. Ein `stat` je Seitenaufruf deckt beides
 * ab: die Angabe im Linktext (AK-20) und den Fehlerzustand (EC-04).
 *
 * ⚠ **Das Paket wird nicht zur Laufzeit gepackt.** Es ist eine committete Datei
 * und wird vom Webserver direkt ausgeliefert; der Front-Controller sieht sie nie,
 * weil `public/.htaccess` nur Anfragen weiterleitet, für die keine Datei
 * existiert. Deshalb gibt es hier nichts zu deckeln — es wird nichts gerechnet
 * (AK-40). Erzeugt wird die Datei von `app:press:package`.
 */
final readonly class PressPackage
{
    /**
     * Pfad unterhalb von `public/`, ohne führenden Schrägstrich.
     *
     * ⚠ **Das Verzeichnis heißt `presse-kit`, nicht `presse` — und das ist kein
     * Geschmack.** Ein Verzeichnis unter `public/`, das so heißt wie eine Route,
     * erzeugt auf Apache eine endlose Weiterleitungsschleife: `mod_dir` schickt
     * `/presse` per 301 auf `/presse/`, weil ein Verzeichnis existiert, und
     * Symfonys Trailing-Slash-Regel schickt es zurück. Der sprachfreie Kurzlink
     * war damit auf Produktion tot (BF-100), während lokal alles lief — der
     * Entwicklungsserver hat kein `mod_dir`.
     *
     * `RouteDirectoryCollisionTest` hält den Fall fest.
     */
    public const string PUBLIC_PATH = 'presse-kit/presse-kit-endlech-lu.zip';

    /** Name der Bedingungsdatei im Paket. Siehe `app:press:package`. */
    public const string TERMS_ENTRY = 'NUTZUNGSBEDINGUNGEN.txt';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * Der Pfad unterhalb von `public/` — als Methode, nicht nur als Konstante.
     *
     * ⚠ **Twig löst `object.attr` niemals über eine Klassenkonstante auf.** Es
     * probiert öffentliche Eigenschaft, dann `attr()`, `getAttr()`, `isAttr()`,
     * `hasAttr()`, dann `__call()` — und wirft danach. `{{ asset(package.publicPath) }}`
     * schlug deshalb mit „Neither the property …" fehl, und zwar **nur im
     * Regelfall**: Der Zweig läuft erst, wenn die Paketdatei existiert. Solange
     * sie fehlte, rendert die Vorlage den Ersatzweg, und der Fehler lag in keinem
     * Testlauf (QA 05, BF-97).
     *
     * `PressDownloadStateTest` legt sein Paket seither selbst an und deckt den
     * Regelfall unabhängig davon ab, ob VB-01 erfüllt ist.
     */
    public function publicPath(): string
    {
        return self::PUBLIC_PATH;
    }

    public function absolutePath(): string
    {
        return $this->projectDir.'/public/'.self::PUBLIC_PATH;
    }

    public function exists(): bool
    {
        return is_file($this->absolutePath());
    }

    /** Größe in Bytes, 0 wenn die Datei fehlt. */
    public function sizeBytes(): int
    {
        return $this->exists() ? (int) filesize($this->absolutePath()) : 0;
    }

    /** Dateiname für den Downloadlink. */
    public function fileName(): string
    {
        return basename(self::PUBLIC_PATH);
    }
}
