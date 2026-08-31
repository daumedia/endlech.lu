<?php

namespace App\Entity;

use App\Enum\BoardIdeaStatus;
use App\Repository\BoardIdeaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Eine Idee zur Plattform auf dem Community-Board (Feature 06).
 *
 * ⚠ **Sichtbarkeit hängt an `$publishedAt`, nicht am Status.** `null` heißt
 * „wartet auf Freigabe", ein gesetztes Datum heißt „öffentlich". Der Status
 * beschreibt eine *öffentliche* Idee und ist eine andere Achse; vermischt
 * könnte ein Statuswechsel eine veröffentlichte Idee vom Netz nehmen.
 * Dadurch ist AK-71 an einer einzigen Bedingung prüfbar.
 *
 * ⚠ **Es gibt kein Feld für den Anzeigenamen.** Er wird bei jeder Anzeige aus
 * `$submittedBy` abgeleitet (`App\Board\AuthorName`). Ein eingefrorener
 * Schnappschuss überlebte die Kontolöschung und wäre genau der Weg zurück zur
 * Person, den AK-68 ausschließt.
 *
 * ⚠ **Es gibt kein Zählerfeld für Zustimmungen.** Gezählt wird in der Abfrage.
 * Ein Zählerfeld liefe auseinander, sobald die Fremdschlüssel-Kaskade beim
 * Kontolöschen Stimmen entfernt — das passiert in der Datenbank, am
 * Anwendungscode vorbei (AK-66).
 */
#[ORM\Entity(repositoryClass: BoardIdeaRepository::class)]
#[ORM\Index(name: 'IDX_board_idea_public', columns: ['published_at', 'status'])]
#[ORM\Index(name: 'IDX_board_idea_queue', columns: ['published_at', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
class BoardIdea
{
    public const int TITLE_MAX = 120;
    public const int DESCRIPTION_MAX = 2000;
    public const int SLUG_MAX = 160;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: self::TITLE_MAX)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    /**
     * ⚠ **Bewusst NICHT unique.** Die Adresse lautet `/{id}-{slug}`; eindeutig
     * macht sie die Kennung, nicht der Slug. Ein Unique-Index erzeugte bei zwei
     * gleichnamigen Ideen einen Serverfehler statt einer zweiten Idee — und
     * gleiche Titel sind auf einem Wunschboard der Normalfall, nicht die
     * Ausnahme.
     */
    #[ORM\Column(length: self::SLUG_MAX)]
    private string $slug = '';

    #[ORM\Column(length: 20, enumType: BoardIdeaStatus::class)]
    private BoardIdeaStatus $status = BoardIdeaStatus::NEW;

    /**
     * `SET NULL`: Die Idee überlebt die Löschung ihres Verfassers. Andere haben
     * für sie gestimmt und das Team hat öffentlich geantwortet — ein
     * Verschwinden risse Lücken in eine öffentliche Zusage (AK-65).
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $submittedBy = null;

    /** Sprache, in der eingereicht wurde — bestimmt die Sprache der Mail (AK-42). */
    #[ORM\Column(length: 5)]
    private string $locale = 'de';

    /** Öffentliche Antwort des Teams; bei einer Ablehnung die Begründung (AK-27, AK-32). */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $teamResponse = null;

    /**
     * Gesetzt = als Dublette zusammengeführt; die Adresse leitet dann auf das Original.
     *
     * ⚠ **`targetEntity` ist Pflicht, weil der Property-Typ `?self` lautet (BF-116).**
     * Ohne die Angabe leitet Doctrine das Ziel aus dem Typ ab — und **PHP 8.4 löst
     * `self` dort nicht zur Klasse auf**, sondern übergibt den Namen wörtlich. Auf
     * Produktion brach `cache:clear` deshalb ab: „The target-entity `App\Entity\self`
     * cannot be found in `App\Entity\BoardIdea#duplicateOf`." Der Deploy vom
     * 2026-08-31 scheiterte daran und musste zurückgerollt werden.
     *
     * ⚠ **Lokal nicht reproduzierbar** — hier läuft PHP 8.5.2, dort greift die
     * Auflösung, und `cache:clear --env=prod` ist grün. Dritter Fall der Sorte
     * „lokal ≠ Produktion" nach `mod_dir` (BF-100) und MySQL 8 gegen MariaDB 10.5.
     *
     * `self::class` wird zur Übersetzungszeit aufgelöst und ist damit von jeder
     * Sprachversion unabhängig. `MappingSelfTargetTest` hält die Regel fest.
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $duplicateOf = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    /** Sperre gegen einen zweiten Mailversand (AK-38, EC-05). */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $notifiedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, BoardVote>
     *
     * ⚠ **Nicht zum Zählen benutzen.** `count($idea->getVotes())` lädt jede
     * Stimme als Objekt. Die Zahl kommt aus `BoardIdeaRepository`, das sie in
     * der Abfrage zählt. Die Zuordnung steht hier nur, damit der JOIN in DQL
     * idiomatisch bleibt.
     */
    #[ORM\OneToMany(targetEntity: BoardVote::class, mappedBy: 'idea')]
    private Collection $votes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->votes = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function beruehrt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * ⚠ **Kürzt auf `SLUG_MAX`.** Der Slugger dehnt aus: Aus „ß" wird „ss", aus
     * einem japanischen Zeichen werden bis zu drei Buchstaben. 120 erlaubte
     * Titelzeichen ergeben so bis zu 360 Slug-Zeichen. Ohne diese Kürzung
     * wanderte der `SQLSTATE[22001]` vom Titel auf den Slug (EC-03) — genau der
     * Fall, den die Projektkonvention „die Prüfung gehört dorthin, wo der Wert
     * hereinkommt" meint.
     */
    public function setSlug(string $slug): static
    {
        $this->slug = mb_substr($slug, 0, self::SLUG_MAX);

        return $this;
    }

    public function getStatus(): BoardIdeaStatus
    {
        return $this->status;
    }

    public function setStatus(BoardIdeaStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSubmittedBy(): ?User
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(?User $submittedBy): static
    {
        $this->submittedBy = $submittedBy;

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

    public function getTeamResponse(): ?string
    {
        return $this->teamResponse;
    }

    public function setTeamResponse(?string $teamResponse): static
    {
        $this->teamResponse = $teamResponse;

        return $this;
    }

    public function getDuplicateOf(): ?self
    {
        return $this->duplicateOf;
    }

    public function setDuplicateOf(?self $duplicateOf): static
    {
        $this->duplicateOf = $duplicateOf;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /** Die eine Frage, an der AK-71 hängt. */
    public function isPublished(): bool
    {
        return null !== $this->publishedAt;
    }

    public function getNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?\DateTimeImmutable $notifiedAt): static
    {
        $this->notifiedAt = $notifiedAt;

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

    /**
     * @return Collection<int, BoardVote>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }
}
