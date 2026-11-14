<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\Restaurant;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use App\Repository\SavedListEntryRepository;
use App\Repository\SavedListRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Eigene Listen (Feature 17): anlegen, umbenennen, löschen, Mitgliedschaften eines Lokals.
 *
 * ⚠ **Die Namensregeln stehen nur hier** — trimmen, NFC, höchstens 60 Zeichen, keine
 * Dublette je Konto (Groß/klein egal, Akzente nicht), höchstens 25 Listen. Übersicht,
 * Umbenennen und „Neue Liste“ auf der Detailseite rufen dieselben Methoden (AK-17 bis AK-20,
 * AK-23, AK-28). Das Formular prüft Pflicht und Länge zusätzlich für die Meldung am Feld; die
 * letzte Instanz ist dieser Dienst — er wird auch ohne Formular gerufen.
 *
 * Prüfen und Schreiben laufen unter der Schreibsperre des Kontos: Zwei Tabs legen nicht
 * gleichzeitig dieselbe Liste oder eine 26. an (EC-05). Rückgabe ist nie eine Ausnahme für
 * eine abgewiesene Eingabe, sondern ein `SavedListResult` mit Meldungsschlüssel.
 */
final readonly class SavedListService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SavedListRepository $lists,
        private SavedListEntryRepository $entries,
        private SavedWriteLock $lock,
    ) {
    }

    public function create(User $user, string $name): SavedListResult
    {
        return $this->lock->forAccount($user, function () use ($user, $name): SavedListResult {
            $fehler = $this->pruefeNeueListe($user, $name);
            if (null !== $fehler) {
                return SavedListResult::rejected($fehler);
            }

            $liste = new SavedList($user, SavedText::name($name), SavedText::nameKey($name));
            $this->em->persist($liste);
            $this->em->flush();

            return SavedListResult::done(true, $liste);
        });
    }

    public function rename(SavedList $list, string $name): SavedListResult
    {
        return $this->lock->forAccount($list->getUser(), function () use ($list, $name): SavedListResult {
            $fehler = $this->pruefeName($name);
            if (null !== $fehler) {
                return SavedListResult::rejected($fehler);
            }

            $schluessel = SavedText::nameKey($name);
            $vorhanden = $this->lists->findOneByUserAndKey($list->getUser(), $schluessel);
            // Den eigenen Namen noch einmal zu speichern — auch in anderer Schreibweise —, ist
            // keine Dublette (AK-28).
            if (null !== $vorhanden && $vorhanden->getId() !== $list->getId()) {
                return SavedListResult::rejected(SavedListResult::NAME_DUPLICATE);
            }

            $neu = SavedText::name($name);
            if ($neu === $list->getName()) {
                return SavedListResult::done(false, $list);
            }

            $list->rename($neu, $schluessel);
            $this->em->flush();

            return SavedListResult::done(true, $list);
        });
    }

    /**
     * Die Einträge fallen über den Fremdschlüssel in der Datenbank mit (AK-30); Herz und
     * Notizen der Lokale bleiben unberührt.
     */
    public function delete(SavedList $list): void
    {
        $this->lock->forAccount($list->getUser(), function () use ($list): void {
            $this->em->remove($list);
            $this->em->flush();
        });
    }

    /**
     * Setzt, in welchen eigenen Listen das Lokal steht, und legt auf Wunsch eine neue Liste mit
     * dem Lokal an — **alles oder nichts** (AK-23): Scheitert der neue Name, bleibt auch jedes
     * Häkchen aus demselben Absenden unverändert.
     *
     * ⚠ `$wantedListIds` muss der Aufrufer vorher gegen die eigenen Listen geprüft haben
     * (fremde Nummer → 404, AK-54). Hier wird eine fremde Nummer zusätzlich nie angefasst: Es
     * werden nur Listen des Kontos gelesen und geändert.
     *
     * @param int[] $wantedListIds
     */
    public function setMemberships(User $user, Restaurant $restaurant, array $wantedListIds, string $newListName = ''): SavedListResult
    {
        return $this->lock->forAccount($user, function () use ($user, $restaurant, $wantedListIds, $newListName): SavedListResult {
            $neueListe = '' !== SavedText::name($newListName);
            if ($neueListe) {
                $fehler = $this->pruefeNeueListe($user, $newListName);
                if (null !== $fehler) {
                    return SavedListResult::rejected($fehler);
                }
            }

            $eigene = [];
            foreach ($this->lists->findByUser($user) as $liste) {
                $eigene[(int) $liste->getId()] = $liste;
            }
            $gewuenscht = array_values(array_intersect(array_map('intval', $wantedListIds), array_keys($eigene)));
            $bisher = $this->entries->findListIdsContaining($user, $restaurant);

            $hinzu = array_diff($gewuenscht, $bisher);
            $weg = array_diff($bisher, $gewuenscht);

            if ([] === $hinzu && [] === $weg && !$neueListe) {
                return SavedListResult::done(false);
            }

            $angelegt = null;
            $this->em->wrapInTransaction(function () use ($eigene, $hinzu, $weg, $restaurant, $neueListe, $user, $newListName, &$angelegt): void {
                foreach ($hinzu as $id) {
                    $this->em->persist(new SavedListEntry($eigene[$id], $restaurant));
                }
                foreach ($weg as $id) {
                    $eintrag = $this->entries->findOneByListAndRestaurant($eigene[$id], $restaurant);
                    if (null !== $eintrag) {
                        $this->em->remove($eintrag);
                    }
                }
                if ($neueListe) {
                    $angelegt = new SavedList($user, SavedText::name($newListName), SavedText::nameKey($newListName));
                    $this->em->persist($angelegt);
                    $this->em->persist(new SavedListEntry($angelegt, $restaurant));
                }
            });

            return SavedListResult::done(true, $angelegt);
        });
    }

    public function removeEntry(SavedList $list, Restaurant $restaurant): bool
    {
        return $this->lock->forAccount($list->getUser(), function () use ($list, $restaurant): bool {
            $eintrag = $this->entries->findOneByListAndRestaurant($list, $restaurant);
            if (null === $eintrag) {
                return false;
            }

            $this->em->remove($eintrag);
            $this->em->flush();

            return true;
        });
    }

    private function pruefeNeueListe(User $user, string $name): ?string
    {
        $fehler = $this->pruefeName($name);
        if (null !== $fehler) {
            return $fehler;
        }

        if (null !== $this->lists->findOneByUserAndKey($user, SavedText::nameKey($name))) {
            return SavedListResult::NAME_DUPLICATE;
        }

        if ($this->lists->countByUser($user) >= SavedList::MAX_PER_USER) {
            return SavedListResult::LIMIT_REACHED;
        }

        return null;
    }

    private function pruefeName(string $name): ?string
    {
        $bereinigt = SavedText::name($name);

        if ('' === $bereinigt) {
            return SavedListResult::NAME_BLANK;
        }

        if (SavedText::length($bereinigt) > SavedList::MAX_NAME_LENGTH) {
            return SavedListResult::NAME_TOO_LONG;
        }

        return null;
    }
}
