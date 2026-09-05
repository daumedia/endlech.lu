<?php

namespace App\Entity;

use App\Enum\AppPlatform;
use App\Enum\WaitlistStatus;
use App\Repository\AppWaitlistEntryRepository;
use App\Waitlist\WaitlistEntryInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Vormerkung für die mobile App (Feature 08).
 *
 * Die dritte Warteliste neben Partnern (B14) und Organisationen (B15) und die
 * schmalste: erfasst werden nur die Adresse und die gewählte Plattform. Ein
 * Name brächte hier keinen Nutzen, den die Adresse nicht schon hat.
 *
 * ⚠ **Eigene Tabelle statt eines Feldes an `PartnerWaitlistEntry`.** Dort sind
 * `restaurant_name`, `contact_name` und `locality` NOT NULL und hier allesamt
 * sinnlos. Ein gemeinsamer Tisch bräuchte sie nullable — und dann prüft
 * niemand mehr, welche Feldkombination gültig ist.
 */
#[ORM\Entity(repositoryClass: AppWaitlistEntryRepository::class)]
#[ORM\Index(name: 'IDX_app_waitlist_status_created', columns: ['status', 'created_at'])]
// ⚠ Benannt deklariert, nicht über `unique: true` an der Spalte: Sonst erfindet
// Doctrine eigene Hash-Namen, und `doctrine:schema:validate` meldet bei jedem
// Lauf eine Abweichung gegen die Migration (Muster aus BoardVote).
#[ORM\UniqueConstraint(name: 'UNIQ_APP_WAITLIST_EMAIL', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_APP_WAITLIST_TOKEN', columns: ['confirmation_token'])]
#[ORM\HasLifecycleCallbacks]
class AppWaitlistEntry implements WaitlistEntryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * ⚠ **Unique — anders als bei den beiden anderen Wartelisten.** Dort legt
     * jeder Submit eine weitere Zeile an; hier ist „eine Adresse, ein Eintrag"
     * ein Akzeptanzkriterium (AK-15). Eine Prüfung allein im Controller
     * verlöre das Wettrennen zweier gleichzeitiger Absendevorgänge aus zwei
     * Tabs — der Index ist die einzige Stelle, an der es entschieden wird.
     */
    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 20, enumType: AppPlatform::class)]
    private ?AppPlatform $platform = null;

    #[ORM\Column(length: 20, enumType: WaitlistStatus::class)]
    private WaitlistStatus $status = WaitlistStatus::PENDING;

    /**
     * Double-Opt-In und zugleich Abmeldetoken.
     *
     * Bleibt nach der Bestätigung absichtlich stehen: nur so lässt sich ein
     * zweiter Klick auf denselben Link von einem unbekannten Token
     * unterscheiden (AK-25 gegen AK-26).
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $confirmationToken = null;

    /** ⚠ Zweideutig wie in B14: auch vom Verwaltungs-Statuswechsel gesetzt. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    /**
     * Zeitpunkt der **Selbst**bestätigung; setzt allein `confirm()` (BF-89).
     *
     * Alles, was eine belegte Adresse voraussetzt — die Übertragung nach Brevo
     * und der Aufräumlauf (AK-47) — fragt hier und nicht bei `confirmedAt`.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $selfConfirmedAt = null;

    /** Zeitpunkt der Einwilligung (DSGVO-Nachweis) – beim Absenden gesetzt. */
    #[ORM\Column]
    private \DateTimeImmutable $consentAt;

    /**
     * Zeitpunkt der **Werbe**-Einwilligung; `null` heißt: keine (Feature 04).
     *
     * Getrennt von `$consentAt`: Jene deckt die Mails zur App selbst, um die es
     * bei der Vormerkung geht. Alles darüber hinaus braucht eine eigene, nicht
     * vorangehakte Entscheidung (Art. 7 Abs. 4 DSGVO).
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $marketingConsentAt = null;

    /**
     * Wann die Mail mit dem Beta-Zugang hinausging; `null` = noch nicht.
     *
     * Festgehalten, weil sie der eigentliche Gegenwert der Eintragung ist:
     * Fragt jemand nach, ist ohne dieses Feld nicht feststellbar, ob die Mail
     * je erzeugt wurde oder ob sie unterwegs verloren ging.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $betaLinkSentAt = null;

    /** Sprache, in der das Formular abgeschickt wurde. */
    #[ORM\Column(length: 5)]
    private string $locale = 'de';

    /** UTM-Quelle oder Referrer-Host, für spätere Attribution. */
    #[ORM\Column(length: 60, nullable: true)]
    private ?string $source = null;

    /** Trägt zugleich die 7-Tage-Frist des Bestätigungslinks (BF-36). */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->consentAt = $now;
        $this->createdAt = $now;
        // PreUpdate feuert beim ersten persist() nicht – daher hier initialisieren.
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * ⚠ **Normalisiert auf Kleinschreibung ohne Randleerzeichen.**
     *
     * `MarketingContactRegistry::sourcesFor()` sucht mit
     * `mb_strtolower(trim(...))`; stünde hier `Max@Example.LU`, fände die
     * Löschkaskade den Eintrag nicht (AK-32) und der Unique-Index griffe bei
     * `max@example.lu` nicht.
     */
    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getPlatform(): ?AppPlatform
    {
        return $this->platform;
    }

    public function setPlatform(?AppPlatform $platform): static
    {
        $this->platform = $platform;

        return $this;
    }

    public function getStatus(): WaitlistStatus
    {
        return $this->status;
    }

    public function setStatus(WaitlistStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    /**
     * Erzeugt den Double-Opt-In-Token. `bin2hex(random_bytes(32))` ergibt genau
     * 64 Zeichen – identisch zu den beiden anderen Wartelisten.
     */
    public function generateConfirmationToken(): string
    {
        $this->confirmationToken = bin2hex(random_bytes(32));

        return $this->confirmationToken;
    }

    /**
     * Stellt einen abgelaufenen Vorgang neu aus: neuer Token **und neue Frist**.
     *
     * ⚠ **BF-117: Der Token allein genügt nicht.** `isExpired()` misst an
     * `createdAt` — eine eigene Ablaufspalte gibt es bewusst nicht. Wurde nur
     * der Token erneuert, war der Link der neuen Mail bereits tot (gemessen:
     * HTTP 410 beim ersten Aufruf), und die Sackgasse, die AK-17 verhindern
     * soll, bestand fort.
     *
     * ⚠ **`consentAt` wandert NICHT mit.** Es ist der Nachweis, wann die
     * Einwilligung erteilt wurde (Art. 7 Abs. 1 DSGVO) — ein Zeitpunkt, den
     * kein späterer Vorgang überschreiben darf. Neu ist die Frist, nicht die
     * Einwilligung.
     *
     * ⚠ **Warum hier und nicht in `WaitlistConfirmationService::register()`:**
     * Dort landet bei B14 und B15 stets ein frisch erzeugter Eintrag, für den
     * `createdAt` ohnehin „jetzt" ist. Diese Warteliste ist die einzige, die
     * einen **bestehenden** Eintrag neu ausstellt — der Fall gehört dorthin,
     * wo es ihn gibt.
     */
    public function renewConfirmationWindow(): string
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this->generateConfirmationToken();
    }

    /**
     * Anzeigename für Listen und Betreffzeilen (Interface-Vertrag).
     *
     * ⚠ Hier steht die **Plattform**, nicht ein Name: Das Feature erhebt
     * keinen. Ein Namensfeld allein zur Erfüllung dieses Vertrags wären
     * erhobene Daten ohne Zweck — das Gegenteil von Datenminimierung.
     */
    public function getDisplayName(): string
    {
        return $this->platform?->label() ?? '';
    }

    /**
     * ⚠ Bewusst leer: Es gibt keinen Ansprechpartner. Der Vertrag verlangt die
     * Methode, und ein leerer String ist die ehrliche Antwort — jede erfundene
     * Anrede landete über das Auftragsbuch in Brevo.
     */
    public function getContactName(): string
    {
        return '';
    }

    public function isConfirmed(): bool
    {
        return null !== $this->confirmedAt;
    }

    /**
     * Der eingelöste Double-Opt-In — der einzige Weg, auf dem
     * `selfConfirmedAt` entsteht (BF-89).
     *
     * Der Status wird nur aus `PENDING` heraus gesetzt (BF-91): Ein
     * fortgeschrittener Stand ist die jüngere Information, und eine späte
     * Bestätigung darf ihn nicht zurückwerfen.
     */
    public function confirm(): static
    {
        $now = new \DateTimeImmutable();

        if (WaitlistStatus::PENDING === $this->status) {
            $this->status = WaitlistStatus::CONFIRMED;
        }

        $this->confirmedAt = $now;
        $this->selfConfirmedAt = $now;

        return $this;
    }

    public function hasSelfConfirmed(): bool
    {
        return null !== $this->selfConfirmedAt;
    }

    public function getSelfConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->selfConfirmedAt;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getConsentAt(): \DateTimeImmutable
    {
        return $this->consentAt;
    }

    public function setConsentAt(\DateTimeImmutable $consentAt): static
    {
        $this->consentAt = $consentAt;

        return $this;
    }

    public function getMarketingConsentAt(): ?\DateTimeImmutable
    {
        return $this->marketingConsentAt;
    }

    public function setMarketingConsentAt(?\DateTimeImmutable $marketingConsentAt): static
    {
        $this->marketingConsentAt = $marketingConsentAt;

        return $this;
    }

    public function hasMarketingConsent(): bool
    {
        return null !== $this->marketingConsentAt;
    }

    public function getBetaLinkSentAt(): ?\DateTimeImmutable
    {
        return $this->betaLinkSentAt;
    }

    public function setBetaLinkSentAt(?\DateTimeImmutable $betaLinkSentAt): static
    {
        $this->betaLinkSentAt = $betaLinkSentAt;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
