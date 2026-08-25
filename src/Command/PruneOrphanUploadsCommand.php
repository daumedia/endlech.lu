<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Findet hochgeladene Dateien, zu denen es keine Datenbankzeile mehr gibt.
 *
 * ⚠ BF-53: Der Entity-Listener verhindert seit dieser Runde, dass neue Waisen
 * entstehen — die alten räumt er nicht weg. Bei der Prüfung von B09 lagen fünf
 * Dateien aus Februar und Juni im Verzeichnis, weiterhin unter ihrer alten
 * Adresse abrufbar. Wer ein Foto löschen ließ, weil es ihn zeigt, hatte es
 * danach immer noch im Netz.
 *
 * Zeigt standardmäßig nur an. Gelöscht wird erst mit `--force` — ein Befehl, der
 * beim ersten Aufruf Dateien entfernt, ist auf einem Produktivsystem eine Falle.
 */
#[AsCommand(
    name: 'app:uploads:prune',
    description: 'Listet hochgeladene Dateien ohne Datenbankzeile (mit --force werden sie gelöscht)',
)]
final class PruneOrphanUploadsCommand extends Command
{
    /**
     * Verzeichnis → Abfrage, die alle dort erwarteten Dateinamen liefert.
     *
     * @var array<string, string>
     */
    private const VERZEICHNISSE = [
        'restaurants' => 'SELECT filename FROM restaurant_image',
        'avatars' => 'SELECT avatar_filename FROM `user` WHERE avatar_filename IS NOT NULL',
    ];

    /** Dateien, die nie zu einer Zeile gehören. */
    private const AUSGENOMMEN = ['.', '..', '.gitkeep'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private readonly string $uploadRoot,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Löscht die gefundenen Dateien wirklich');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loeschen = (bool) $input->getOption('force');
        $gesamt = 0;

        foreach (self::VERZEICHNISSE as $ordner => $abfrage) {
            $pfad = $this->uploadRoot.'/'.$ordner;
            if (!is_dir($pfad)) {
                $io->warning(sprintf('%s gibt es nicht.', $pfad));
                continue;
            }

            $bekannt = array_flip(array_map(
                static fn (array $zeile) => (string) reset($zeile),
                $this->connection->fetchAllAssociative($abfrage),
            ));

            $waisen = [];
            foreach (scandir($pfad) ?: [] as $datei) {
                if (\in_array($datei, self::AUSGENOMMEN, true) || is_dir($pfad.'/'.$datei)) {
                    continue;
                }
                if (!isset($bekannt[$datei])) {
                    $waisen[] = $datei;
                }
            }

            $io->section(sprintf('%s — %d Datei(en) ohne Zeile', $ordner, \count($waisen)));

            foreach ($waisen as $datei) {
                $voll = $pfad.'/'.$datei;
                $alter = date('Y-m-d', (int) filemtime($voll));
                $groesse = round(((int) filesize($voll)) / 1024);

                if ($loeschen && @unlink($voll)) {
                    $io->writeln(sprintf('  <fg=red>gelöscht</> %s (%s, %d KB)', $datei, $alter, $groesse));
                } elseif ($loeschen) {
                    $io->writeln(sprintf('  <fg=yellow>nicht löschbar</> %s', $datei));
                } else {
                    $io->writeln(sprintf('  %s (%s, %d KB)', $datei, $alter, $groesse));
                }
            }

            $gesamt += \count($waisen);
        }

        if (0 === $gesamt) {
            $io->success('Keine verwaisten Dateien.');
        } elseif ($loeschen) {
            $io->success(sprintf('%d Datei(en) entfernt.', $gesamt));
        } else {
            $io->note(sprintf('%d Datei(en) gefunden. Zum Löschen: bin/console app:uploads:prune --force', $gesamt));
        }

        return Command::SUCCESS;
    }
}
