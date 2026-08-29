<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\MarketingOrigin;
use App\Marketing\MarketingContactRegistry;
use App\Repository\OrganisationWaitlistEntryRepository;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Waitlist\WaitlistEntryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Überträgt den **Bestand** der beiden Wartelisten einmalig ins Auftragsbuch.
 *
 * Seit August 2026 haben sich Anmeldungen angesammelt, die es nie nach Brevo
 * geschafft haben – Feature 04 gab es damals noch nicht. Dieser Befehl holt
 * genau das nach; laufende Anmeldungen trägt der WaitlistConfirmationService
 * beim Bestätigen selbst ein.
 *
 * ⚠ **Der Trockenlauf ist der Vorgabefall** (AK-21). Ohne `--commit` wird
 * nichts geschrieben und nichts verschickt – ausgegeben werden nur Zahl und
 * Liste der Einträge, die übertragen würden. Das ist bewusst **umgekehrt** zum
 * `--force` von `app:metrics:snapshot`: Die gefährliche Richtung braucht die
 * Flagge, nicht die harmlose. Ein falsch gefilterter Lauf ist nicht
 * zurückzuholen – die Mails sind dann raus.
 *
 * ⚠ **Die Auswahlregel steht hier und ist nicht per Parameter aufweichbar**
 * (AK-23): ausschließlich **bestätigte** Wartelisten-Einträge mit
 * Werbe-Einwilligung. **Keine Nutzerkonten** – die haben der Nutzung
 * zugestimmt, nicht der Werbung. **Keine unbestätigten** – wer den
 * Double-Opt-In nie abschloss, hat nie belegt, dass die Adresse ihm gehört.
 * Es gibt deshalb absichtlich kein `--include-users`, kein `--all` und kein
 * `--status=`: Eine Option, die diese Regel lockert, wäre irgendwann gesetzt.
 *
 * ⚠ **Dieser Befehl ruft Brevo nicht.** Er schreibt nur den Auftrag; die
 * Übertragung erledigt der reguläre Lauf von `app:marketing:sync`.
 *
 * ⚠ **Kein HTTP-Zugang** (AK-36). Der Befehl hängt an keiner Route und ist
 * damit strukturell nur für den erreichbar, der eine Konsole auf dem Server
 * hat – kein Rollen-Check kann daran vorbei. Wer keinen SSH-Zugang hat, kann
 * die Bestandsübertragung nicht auslösen.
 *
 * ⚠ **Die Ausgabe maskiert die Adressen** (AK-31). Sie landet im Terminal, in
 * Logdateien und womöglich in einem Ticket. Der Betreiber muss sehen, *welche*
 * Einträge betroffen sind – dafür genügen Herkunft, Name und Status.
 */
#[AsCommand(
    name: 'app:marketing:import',
    description: 'Trägt den Bestand der bestätigten Wartelisten ins Auftragsbuch ein (Trockenlauf ohne --commit)',
)]
final class MarketingImportCommand extends Command
{
    public function __construct(
        private readonly PartnerWaitlistEntryRepository $partnerEntries,
        private readonly OrganisationWaitlistEntryRepository $organisationEntries,
        private readonly MarketingContactRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'commit',
            null,
            InputOption::VALUE_NONE,
            'Schreibt die angezeigten Einträge wirklich ins Auftragsbuch (ohne diese Flagge: Trockenlauf)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $schreiben = (bool) $input->getOption('commit');

        $auswahl = $this->auswahl();
        $kandidaten = $auswahl['eintraege'];
        $doppelt = $auswahl['doppelt'];

        if ([] === $kandidaten) {
            $io->success('Kein bestätigter Wartelisten-Eintrag mit Werbe-Einwilligung – nichts zu übertragen.');

            return Command::SUCCESS;
        }

        $zeilen = [];
        $uebertragen = 0;
        $uebersprungen = 0;

        foreach ($kandidaten as $eintrag) {
            // Auch im Trockenlauf läuft die Registry: Nur sie kennt eine
            // bestehende Sperre, und nur so zeigt die Vorschau denselben Kreis,
            // den `--commit` danach wirklich schreibt (AK-22). Geschrieben wird
            // dabei nichts – `recordWaitlistEntry()` flusht nicht.
            $kontakt = $this->registry->recordWaitlistEntry($eintrag);

            if (null === $kontakt) {
                ++$uebersprungen;
                continue;
            }

            ++$uebertragen;
            $zeilen[] = [
                $this->herkunft($eintrag),
                $eintrag->getDisplayName(),
                $this->maskiert($eintrag->getEmail()),
                $eintrag->getStatus()->label(),
            ];
        }

        $io->section($schreiben
            ? sprintf('%d Eintrag/Einträge werden übertragen', $uebertragen)
            : sprintf('%d Eintrag/Einträge würden übertragen', $uebertragen));

        $io->table(['Herkunft', 'Anzeigename', 'E-Mail (maskiert)', 'Status'], $zeilen);

        $io->definitionList(
            [$schreiben ? 'Übertragen' : 'Zu übertragen' => (string) $uebertragen],
            ['Übersprungen (Sperre im Auftragsbuch)' => (string) $uebersprungen],
            ['Betroffene Wartelisten-Einträge' => (string) \count($kandidaten)],
        );

        if ($doppelt > 0) {
            $io->text(sprintf(
                '%d weitere Anmeldung(en) tragen eine bereits gelistete Adresse; maßgeblich ist die jüngste Einwilligung.',
                $doppelt,
            ));
        }

        if (!$schreiben) {
            // ⚠ AK-21: Der Trockenlauf darf nichts hinterlassen. `flush()` steht
            // hier nicht – und `clear()` wirft die vorgemerkten Änderungen
            // zusätzlich aus dem UnitOfWork, damit sie auch niemand später
            // versehentlich mitschreibt.
            $this->entityManager->clear();

            $io->note('Trockenlauf: nichts geschrieben, nichts verschickt. Zum Übertragen: bin/console app:marketing:import --commit');

            return Command::SUCCESS;
        }

        // Ein einziger flush() für den ganzen Bestand: Die Übertragung ist
        // entweder vollständig oder gar nicht passiert.
        $this->entityManager->flush();

        $io->success(sprintf('%d Eintrag/Einträge ins Auftragsbuch geschrieben.', $uebertragen));
        $io->note('Damit ist noch nichts bei Brevo. Die eigentliche Übertragung erledigt der reguläre Lauf von `bin/console app:marketing:sync`.');

        return Command::SUCCESS;
    }

    /**
     * Der Kreis der Betroffenen – und nichts sonst.
     *
     * Bestätigt **und** mit Werbe-Einwilligung, aus beiden Wartelisten, nach
     * Einwilligungszeitpunkt aufsteigend. Die Regel steht bewusst als Code hier
     * und nicht als Repository-Methode mit Parametern (AK-23).
     *
     * Dieselbe Adresse kann auf beiden Wartelisten stehen. Im Auftragsbuch ist
     * sie trotzdem **eine** Zeile (EC-01, Unique-Index auf `email`) – ohne die
     * Entdopplung liefe der `flush()` unten in eine Unique-Verletzung. Weil die
     * Liste aufsteigend sortiert ist, gewinnt die jüngste Einwilligung.
     *
     * @return array{eintraege: list<WaitlistEntryInterface>, doppelt: int}
     */
    private function auswahl(): array
    {
        /** @var list<WaitlistEntryInterface> $alle */
        $alle = [
            ...$this->partnerEntries->findFiltered(null, 'ASC'),
            ...$this->organisationEntries->findFiltered(null, null, 'ASC'),
        ];

        // ⚠ BF-89: **`hasSelfConfirmed()`, nicht `isConfirmed()`.** Letzteres
        // ist auch nach einem Verwaltungs-Statuswechsel wahr; die
        // Bestandsübertragung hätte darüber Adressen mitgenommen, deren
        // Inhaber den Bestätigungslink nie angeklickt hat — und die eigene
        // Ausgabe zeigte sie dabei als „Unbestätigt" an.
        $kandidaten = array_values(array_filter(
            $alle,
            static fn (WaitlistEntryInterface $eintrag): bool => $eintrag->hasSelfConfirmed() && $eintrag->hasMarketingConsent(),
        ));

        usort(
            $kandidaten,
            static fn (WaitlistEntryInterface $a, WaitlistEntryInterface $b): int => ($a->getMarketingConsentAt() ?? $a->getCreatedAt())
                <=> ($b->getMarketingConsentAt() ?? $b->getCreatedAt()),
        );

        $jeAdresse = [];

        foreach ($kandidaten as $eintrag) {
            // Kleingeschrieben vergleichen wie `MarketingContact::setEmail()`,
            // sonst gälten „Anna@…" und „anna@…" als zwei Menschen.
            $jeAdresse[mb_strtolower(trim($eintrag->getEmail()))] = $eintrag;
        }

        // Der überschreibende Eintrag erbt den Platz des überschriebenen –
        // nach der Entdopplung steht die Liste deshalb nicht mehr chronologisch.
        $entdoppelt = array_values($jeAdresse);
        usort(
            $entdoppelt,
            static fn (WaitlistEntryInterface $a, WaitlistEntryInterface $b): int => ($a->getMarketingConsentAt() ?? $a->getCreatedAt())
                <=> ($b->getMarketingConsentAt() ?? $b->getCreatedAt()),
        );

        return [
            'eintraege' => $entdoppelt,
            'doppelt' => \count($kandidaten) - \count($entdoppelt),
        ];
    }

    /**
     * Herkunft für die Anzeige.
     *
     * Maßgeblich für das Auftragsbuch bleibt die Zuordnung in der Registry;
     * hier steht sie nur, damit der Betreiber in der Tabelle sieht, aus welcher
     * Liste ein Eintrag stammt.
     */
    private function herkunft(WaitlistEntryInterface $eintrag): string
    {
        if ($eintrag instanceof PartnerWaitlistEntry) {
            return MarketingOrigin::PARTNER->label();
        }

        if ($eintrag instanceof OrganisationWaitlistEntry) {
            $typ = $eintrag->getType();

            return null === $typ
                ? MarketingOrigin::COMPANY->label()
                : MarketingOrigin::fromOrganisationType($typ)->label();
        }

        return '—';
    }

    /**
     * `mika@example.lu` → `m***@example.lu` (AK-31).
     *
     * Die Domain bleibt stehen: Sie hilft beim Wiedererkennen und ist für sich
     * genommen keine Adresse. Der lokale Teil ist die Adresse.
     */
    private function maskiert(string $email): string
    {
        $at = mb_strrpos($email, '@');

        if (false === $at || 0 === $at) {
            return '***';
        }

        return mb_substr($email, 0, 1).'***'.mb_substr($email, $at);
    }
}
