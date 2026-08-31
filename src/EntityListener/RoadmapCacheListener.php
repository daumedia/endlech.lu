<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\BoardIdea;
use App\Entity\BoardVote;
use App\Entity\User;
use App\Roadmap\CommunityRoadmap;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

/**
 * Wirft den Zwischenspeicher der öffentlichen Roadmap weg, sobald sich etwas
 * ändert, das dort sichtbar ist (Feature 07, AK-18, AK-47).
 *
 * Als Entity-Listener statt als Aufruf in `BoardModerator` und `BoardVoteService`:
 * So bleibt Feature `06` unberührt, und **jeder** Schreibweg ist erfasst — auch die,
 * die es heute noch nicht gibt. Fünf ausdrückliche Aufrufe wären fünf Stellen, und
 * die sechste wäre die, die jemand vergisst.
 *
 * ⚠ **`User::postRemove` ist kein Beiwerk, sondern der Grund für diese Klasse.**
 * Beim Löschen eines Kontos fallen die Stimmen über die Fremdschlüssel-Kaskade
 * **in der Datenbank** weg — das steht so im Changelog von Feature `06` („das
 * geschieht in der Datenbank, am Anwendungscode vorbei"). Doctrine sieht dieses
 * Entfernen nicht und feuert für `BoardVote` nichts. Ohne diesen Fall stünde bis zu
 * eine Stunde lang eine zu hohe Zustimmungszahl auf der Roadmap.
 *
 * Die Lebensdauer von einer Stunde bleibt als zweites Netz für Wege, an die beim
 * Entwurf niemand gedacht hat (OF-07).
 *
 * ⚠ **`postUpdate` deckt den wichtigsten Fall ab**: Ein Statuswechsel im Board ist
 * eine Änderung an einer bestehenden Idee, kein Anlegen. Eine Idee, die auf
 * `Abgelehnt` gesetzt oder depubliziert wird, verschwindet damit beim nächsten
 * Aufruf — ohne Deploy und ohne weiteren Handgriff.
 */
#[AsEntityListener(event: Events::postPersist, entity: BoardIdea::class)]
#[AsEntityListener(event: Events::postUpdate, entity: BoardIdea::class)]
#[AsEntityListener(event: Events::postRemove, entity: BoardIdea::class)]
#[AsEntityListener(event: Events::postPersist, entity: BoardVote::class)]
#[AsEntityListener(event: Events::postRemove, entity: BoardVote::class)]
#[AsEntityListener(event: Events::postRemove, entity: User::class)]
final readonly class RoadmapCacheListener
{
    public function __construct(
        private CommunityRoadmap $roadmap,
    ) {
    }

    public function postPersist(object $entity): void
    {
        $this->roadmap->invalidate();
    }

    public function postUpdate(object $entity): void
    {
        $this->roadmap->invalidate();
    }

    public function postRemove(object $entity): void
    {
        $this->roadmap->invalidate();
    }
}
