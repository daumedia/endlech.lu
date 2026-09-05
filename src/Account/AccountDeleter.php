<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use App\Marketing\MarketingContactRegistry;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\BoardIdeaRepository;
use App\Repository\UserRepository;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Löscht ein Konto endgültig (Art. 17 DSGVO).
 *
 * Was verschwindet: der Nutzerdatensatz, seine Passkeys (Kaskade), die
 * Avatar-Datei im Dateisystem und die App-Vormerkung unter derselben Adresse
 * (Feature 08 / AK-50 — ⚠ nur diese eine Warteliste, siehe unten).
 *
 * ⚠ **Was bleibt, ist eine Entscheidung und keine Nachlässigkeit:** Restaurants
 * und Vorschläge, die dieser Nutzer eingereicht hat, überleben — ihr Bezug auf
 * die Person wird über `ON DELETE SET NULL` gekappt. Eine Angabe darüber, ob ein
 * Lokal eine Rampe hat, ist eine Sachangabe; sie mitzulöschen nähme anderen
 * Menschen eine Auskunft weg, die sie brauchen, und wäre von Art. 17 nicht
 * gefordert.
 *
 * ⚠ **Der letzte Admin kann sich nicht selbst löschen.** Das Projekt hat genau
 * ein Admin-Konto (B19/FB-01); ohne diese Sperre wäre der Verwaltungsbereich nach
 * einem unbedachten Klick unerreichbar, und es gäbe keinen Weg zurück.
 */
final readonly class AccountDeleter
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $users,
        private AvatarUploadService $avatars,
        private MarketingContactRegistry $marketingContacts,
        private BoardIdeaRepository $boardIdeas,
        private AppWaitlistEntryRepository $appWaitlist,
    ) {
    }

    public function istLetzterAdmin(User $user): bool
    {
        if (!\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        return 1 >= \count($this->users->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :rolle')
            ->setParameter('rolle', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult());
    }

    public function delete(User $user): void
    {
        // Erst die Datei, dann die Zeile: Ein Rollback nach dem `unlink` ließe ein
        // Konto ohne Bild zurück — ärgerlich, aber reparierbar. Umgekehrt bliebe
        // ein Bild ohne Konto liegen, und genau das ist der Fehler, den BF-53
        // beschreibt.
        if (null !== $user->getAvatarFilename()) {
            $this->avatars->delete($user);
        }

        // Feature 08 / AK-50: Die Vormerkung für die App steht unter derselben
        // Adresse und geht mit dem Konto. Sie muss **vor** `scheduleRemoval()`
        // fallen, denn dieser Aufruf zählt, welche Quellen unter der Adresse
        // noch eine gültige Werbe-Einwilligung tragen (BF-84, gleich darunter):
        // Stünde die Vormerkung dabei noch, bliebe der Brevo-Kontakt am Leben
        // und hinge danach an einer Zeile, die es nicht mehr gibt. Dasselbe
        // Muster wie beim Avatar und beim Löschauftrag — erst das Abhängige,
        // dann die Zeile.
        //
        // ⚠ **Das `flush()` hier ist keine Zierde.** `scheduleRemoval()` sucht
        // die Quellen über eine Abfrage, und Doctrine liefert eine bloß
        // vorgemerkte Löschung weiterhin aus dem Identity Map mit. Ohne den
        // Schreibvorgang wäre die Reihenfolge auf dem Papier richtig und in der
        // Wirkung wirkungslos. Bricht danach etwas ab, steht ein Konto ohne
        // Vormerkung da — ärgerlich und nachholbar; umgekehrt bliebe eine
        // Adresse in Brevo, deren Löschung jemand ausdrücklich verlangt hat.
        //
        // ⚠ **Bewusste Abweichung vom Bestandsverhalten.** Partner- und
        // Organisationseinträge bleiben beim Kontolöschen ausdrücklich stehen;
        // der BF-84-Hinweis unten begründet das damit, dass eine eigenständige
        // Wartelisten-Einwilligung nicht am Konto hängt. Für die App-Warteliste
        // ist am 2026-09-04 anders entschieden worden — mitlöschen. Damit
        // verhalten sich **drei Wartelisten in derselben Lage unterschiedlich**;
        // der Widerspruch ist bekannt und nicht aufgelöst. OF-08 in
        // `features/08-app-warteliste/spec.md` hält die Frage offen, ob es
        // dabei bleibt oder B14/B15 nachziehen.
        $appEntry = $this->appWaitlist->findOneByEmail($user->getEmail());

        if (null !== $appEntry) {
            $this->em->remove($appEntry);
            $this->em->flush();
        }

        // Feature 04 / AK-14: Dieselbe Reihenfolge wie beim Avatar — erst das
        // Auswärtige, dann die Zeile. Der Löschauftrag muss stehen, bevor das
        // Konto verschwindet; danach wüsste niemand mehr, dass in Brevo noch
        // eine Adresse liegt. Eine Löschung nach Art. 17, die einen Kontakt bei
        // einem Dritten stehen lässt, ist keine.
        //
        // Der Auftrag wird nur gestellt, nicht ausgeführt: Scheitert Brevo,
        // bleibt die lokale Löschung trotzdem bestehen (AK-16) und der Lauf
        // holt es nach.
        // ⚠ BF-84: Das Konto geht als auslösende Quelle mit — steht dieselbe
        // Adresse noch auf einer Warteliste mit gültiger Einwilligung, bleibt
        // der Kontakt dort bestehen, statt mitgelöscht zu werden.
        $this->marketingContacts->scheduleRemoval($user->getEmail(), $user);

        // Feature 06 / EC-09: Noch nicht freigegebene Ideen verschwinden mit dem
        // Konto. Der Fremdschlüssel ist `SET NULL` — richtig für eine
        // veröffentlichte Idee (AK-65: andere haben zugestimmt, das Team hat
        // geantwortet), falsch für eine wartende: Die bliebe als herrenlose
        // Einreichung in der Moderationsschlange stehen, und eine spätere
        // Freigabe schickte eine Mail an eine Adresse, die es nicht mehr gibt.
        //
        // Abgegebene Zustimmungen brauchen hier nichts: Sie kaskadieren über den
        // Fremdschlüssel, und weil die Zahl gezählt und nicht mitgeführt wird,
        // stimmt sie danach von allein (AK-66).
        $this->boardIdeas->deleteUnpublishedBy($user);

        $this->em->remove($user);
        $this->em->flush();
    }
}
