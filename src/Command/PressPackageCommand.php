<?php

namespace App\Command;

use App\Press\PressPackage;
use App\Press\PressRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Packt das Materialpaket für die Presseseite.
 *
 * Läuft **von Hand** und nicht im Request und nicht im Deploy. Das Ergebnis
 * (`public/presse-kit/presse-kit-endlech-lu.zip`) wird committet: Zur Laufzeit zu
 * packen fiele unter die Projektkonvention „ein Weg, der bei jedem Aufruf den
 * ganzen Bestand lädt, braucht einen Deckel", und ein Schritt im Deploy wäre
 * einer mehr, den man bei jedem Auslieferungsfehler mit untersuchen muss.
 *
 * ⚠ **Der Befehl ist die einzige Stelle, die das Paket erzeugt, und er liest
 * dieselbe Liste wie die Seite** (`PressRegistry::assets()`). Damit können
 * Vorschau und Paketinhalt nicht auseinanderlaufen; `PressPackageTest` prüft
 * genau das an der committeten Datei nach (AK-17).
 *
 * ⚠ **Die Nutzungsbedingungen entstehen hier aus dem Übersetzer**, nicht aus
 * einer zweiten, von Hand gepflegten Textdatei. AK-22 sagt zu, dass im Paket
 * dieselben Bedingungen stehen wie auf der Seite — mit zwei Quellen wäre das
 * eine Hoffnung.
 *
 * ⚠ **Fehlt eine Datei, bricht der Befehl ab und schreibt kein Paket.** Ein
 * halbes Presse-Kit ist schlechter als keines: Wer es herunterlädt, merkt erst
 * beim Layouten, dass die Dunkelvariante fehlt.
 */
#[AsCommand(
    name: 'app:press:package',
    description: 'Packt Logos, Porträt und Nutzungsbedingungen zum Presse-Paket',
)]
final class PressPackageCommand extends Command
{
    /** Sprachreihenfolge in der Bedingungsdatei — Vorgabesprache zuerst. */
    private const array LOCALES = ['lb', 'de', 'fr', 'en'];

    public function __construct(
        private readonly PressRegistry $registry,
        private readonly PressPackage $package,
        private readonly TranslatorInterface $translator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.press_email%')]
        private readonly string $pressEmail,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!class_exists(\ZipArchive::class)) {
            $io->error('Die PHP-Erweiterung „zip" fehlt. Ohne sie lässt sich das Paket nicht packen.');

            return Command::FAILURE;
        }

        $public = $this->projectDir.'/public/';
        $fehlend = [];
        foreach ($this->registry->assets() as $asset) {
            if (!is_file($public.$asset->publicPath)) {
                $fehlend[] = $asset->publicPath;
            }
        }

        if ([] !== $fehlend) {
            $io->error('Es fehlen Dateien — es wird kein Paket geschrieben:');
            $io->listing($fehlend);
            $io->note('Die vier Vektormarken sind Vorbedingung VB-01 aus features/05-presse-kit/spec.md.');

            return Command::FAILURE;
        }

        $ziel = $this->package->absolutePath();
        if (!is_dir(\dirname($ziel)) && !mkdir($verzeichnis = \dirname($ziel), 0o775, true) && !is_dir($verzeichnis)) {
            $io->error(sprintf('Verzeichnis %s ließ sich nicht anlegen.', \dirname($ziel)));

            return Command::FAILURE;
        }

        $zip = new \ZipArchive();
        if (true !== $zip->open($ziel, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $io->error(sprintf('Paket %s ließ sich nicht öffnen.', $ziel));

            return Command::FAILURE;
        }

        foreach ($this->registry->assets() as $asset) {
            $zip->addFile($public.$asset->publicPath, $asset->fileName());
        }
        $zip->addFromString(PressPackage::TERMS_ENTRY, $this->nutzungsbedingungen());
        $zip->close();

        clearstatcache(true, $ziel);
        $io->success(sprintf(
            'Paket geschrieben: %s (%d Dateien, %s)',
            $this->package->fileName(),
            \count($this->registry->assets()) + 1,
            $this->lesbareGroesse($this->package->sizeBytes()),
        ));
        $io->note('Die Datei gehört in denselben Commit wie die Änderung, die sie ausgelöst hat.');

        return Command::SUCCESS;
    }

    /**
     * Die Bedingungsdatei — vier Sprachabschnitte in einer Datei.
     *
     * Eine Datei und nicht vier: Wer das Paket entpackt, soll die Bedingungen
     * finden und nicht auswählen müssen. Vier Dateien laden dazu ein, drei davon
     * zu löschen — und die gelöschte ist die des nächsten Lesers.
     */
    private function nutzungsbedingungen(): string
    {
        $zeilen = ['Endlech.lu – '.$this->translator->trans('material.terms_title', [], 'press', 'de')];
        $zeilen[] = str_repeat('=', 60);

        foreach (self::LOCALES as $locale) {
            $t = fn (string $key): string => $this->translator->trans($key, [], 'press', $locale);

            $zeilen[] = '';
            $zeilen[] = strtoupper($locale).' — '.$t('material.terms_title');
            $zeilen[] = str_repeat('-', 60);
            $zeilen[] = $t('material.terms_intro');
            $zeilen[] = '';
            $zeilen[] = $t('material.allowed_title').':';
            foreach (['allowed_1', 'allowed_2', 'allowed_3', 'allowed_4'] as $key) {
                $zeilen[] = '  + '.$t('material.'.$key);
            }
            $zeilen[] = '';
            $zeilen[] = $t('material.forbidden_title').':';
            foreach (['forbidden_1', 'forbidden_2', 'forbidden_3', 'forbidden_4'] as $key) {
                $zeilen[] = '  - '.$t('material.'.$key);
            }
            $zeilen[] = '';
            $zeilen[] = $this->translator->trans(
                'material.terms_contact',
                ['%email%' => $this->pressEmail],
                'press',
                $locale,
            );
        }

        return implode("\n", $zeilen)."\n";
    }

    private function lesbareGroesse(int $bytes): string
    {
        return $bytes >= 1024 * 1024
            ? sprintf('%.1f MB', $bytes / 1024 / 1024)
            : sprintf('%d kB', (int) ceil($bytes / 1024));
    }
}
