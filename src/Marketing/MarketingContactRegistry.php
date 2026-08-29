<?php

namespace App\Marketing;

use App\Entity\MarketingContact;
use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Entity\User;
use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Enum\WaitlistStatus;
use App\Repository\MarketingContactRepository;
use App\Waitlist\WaitlistEntryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Führt das Auftragsbuch `marketing_contact` (Feature 04).
 *
 * ⚠ **Diese Klasse ruft Brevo nicht.** Sie schreibt nur, was dort stehen soll;
 * ein wiederkehrender Konsolenbefehl arbeitet das ab. Das ist keine Vorliebe,
 * sondern die einzige Bauweise, die hier trägt: Auf Production läuft
 * `MESSENGER_TRANSPORT_DSN=sync://` und kein Worker (BF-48). Eine per Messenger
 * „asynchron" verschickte Nachricht liefe dort **synchron im Request** – und
 * damit hinge jede Wartelisten-Anmeldung an der Erreichbarkeit eines fremden
 * Dienstes. Genau das verbietet AK-17.
 *
 * ⚠ **Kein `flush()`.** Die Aufrufer stehen mitten in einem eigenen Vorgang
 * (Bestätigung, Kontolöschung) und schließen ihn selbst ab. Ein `flush()` von
 * hier aus schriebe deren halbfertigen Zustand mit.
 */
class MarketingContactRegistry
{
    /**
     * In diesem Vorgang angelegte, noch nicht geschriebene Zeilen.
     *
     * ⚠ BF-85: `findOneByEmail()` fragt die Datenbank und sieht eine mit
     * `persist()` vorgemerkte Zeile **nicht**. Zwei `record()`-Aufrufe für
     * dieselbe Adresse ohne `flush()` dazwischen legten deshalb zwei Entities
     * an, und der gemeinsame `flush()` scheiterte am Unique-Index — mit einer
     * `UniqueConstraintViolationException`, die den ganzen Vorgang mitriss.
     * `MarketingImportCommand` musste aus genau diesem Grund von Hand
     * entdoppeln.
     *
     * @var array<string, MarketingContact> kleingeschriebene Adresse => Zeile
     */
    private array $vorgemerkt = [];

    public function __construct(
        private readonly MarketingContactRepository $contacts,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Sucht die Zeile — erst unter den vorgemerkten, dann in der Datenbank.
     *
     * Eine vorgemerkte Zeile, die der EntityManager nicht mehr führt (nach
     * einem `clear()`, wie es der Trockenlauf des Imports macht), wird
     * verworfen: Sie zeigte sonst auf einen Zustand, den es nicht mehr gibt.
     */
    private function finde(string $email): ?MarketingContact
    {
        $schluessel = mb_strtolower(trim($email));

        if (isset($this->vorgemerkt[$schluessel])) {
            if ($this->entityManager->contains($this->vorgemerkt[$schluessel])) {
                return $this->vorgemerkt[$schluessel];
            }

            unset($this->vorgemerkt[$schluessel]);
        }

        return $this->contacts->findOneByEmail($email);
    }

    /**
     * Trägt eine bestätigte Wartelisten-Anmeldung ins Auftragsbuch ein.
     *
     * Gibt `null` zurück, wenn nichts zu tun war – das ist der Normalfall und
     * kein Fehler.
     */
    public function recordWaitlistEntry(WaitlistEntryInterface $entry): ?MarketingContact
    {
        // AK-05: Wer den Double-Opt-In nie abgeschlossen hat, hat nie belegt,
        // dass die Adresse ihm gehört. Er geht nicht nach Brevo (EC-03).
        //
        // ⚠ BF-89: **`hasSelfConfirmed()`, nicht `isConfirmed()`.** Letzteres
        // ist auch nach einem Verwaltungs-Statuswechsel wahr — darüber gelangte
        // eine nie bestätigte Adresse nach Brevo. Der Unterschied ist der
        // ganze Punkt dieser Zeile.
        if (!$entry->hasSelfConfirmed() || !$entry->hasMarketingConsent()) {
            return null;
        }

        return $this->record(
            email: $entry->getEmail(),
            origin: $this->originOf($entry),
            consentAt: $entry->getMarketingConsentAt() ?? new \DateTimeImmutable(),
            locale: $entry->getLocale(),
            contactName: $entry->getContactName(),
            organisationName: $entry->getDisplayName(),
            funnelStatus: $entry->getStatus(),
        );
    }

    /**
     * Trägt ein bestätigtes Nutzerkonto ins Auftragsbuch ein.
     */
    public function recordUser(User $user): ?MarketingContact
    {
        // AK-05: dieselbe Regel wie bei den Wartelisten – erst die bestätigte
        // Adresse, dann der Kontakt.
        if (!$user->isVerified() || !$user->hasMarketingConsent()) {
            return null;
        }

        return $this->record(
            email: $user->getEmail(),
            origin: MarketingOrigin::ACCOUNT,
            consentAt: $user->getMarketingConsentAt() ?? new \DateTimeImmutable(),
            locale: 'de',
            contactName: $user->getName(),
            organisationName: null,
            // Ein Konto durchläuft keinen Vertriebstrichter – das Attribut
            // bleibt leer, statt einen Status vorzutäuschen.
            funnelStatus: null,
        );
    }

    /**
     * Legt die Zeile an oder schreibt sie fort und stellt sie auf `pending`.
     *
     * Gibt `null` zurück, wenn eine bestehende **Sperre** die Eintragung
     * verhindert.
     */
    public function record(
        string $email,
        MarketingOrigin $origin,
        \DateTimeImmutable $consentAt,
        string $locale,
        ?string $contactName = null,
        ?string $organisationName = null,
        ?WaitlistStatus $funnelStatus = null,
    ): ?MarketingContact {
        $contact = $this->finde($email);

        if (null === $contact) {
            $contact = new MarketingContact();
            $contact->setEmail($email);
            $this->entityManager->persist($contact);
            $this->vorgemerkt[$contact->getEmail()] = $contact;
        } elseif ($this->wouldStayBlocked($contact, $consentAt)) {
            // AK-12: Wer sich abgemeldet hat, wird vom nächsten Lauf nicht
            // erneut eingetragen. Die Zeile bleibt als Sperre stehen.
            return null;
        }

        $contact
            ->setOrigin($origin)
            // AK-45: Der jüngere Zeitpunkt gewinnt. Liegt die Einwilligung nach
            // dem Widerruf, ist die Sperre überholt und die Adresse wieder
            // frei – `revokedAt` bleibt als Historie stehen, `isBlocked()`
            // vergleicht die beiden Zeitpunkte.
            ->setConsentAt($consentAt)
            ->setLocale($locale)
            ->setContactName($contactName)
            ->setOrganisationName($organisationName)
            ->setFunnelStatus($funnelStatus)
            ->setSyncState(MarketingSyncState::PENDING)
            ->setLastError(null)
            // Ein neuer Auftrag verdient neue Versuche: Sonst bliebe eine
            // Adresse, die vor Wochen fünfmal scheiterte, für immer liegen.
            ->setAttempts(0);

        return $contact;
    }

    /**
     * Schreibt eine bestätigte Adressänderung im Auftragsbuch fort (EC-02).
     *
     * Brevo adressiert den Kontakt über `ext_id`, also über die Kennung dieser
     * Zeile, und bekommt die neue Adresse als Attribut mit. Deshalb ist der
     * Wechsel dort eine Änderung und kein zweiter Kontakt – Voraussetzung ist
     * aber, dass hier **dieselbe** Zeile weitergeführt wird. Ein `record()` mit
     * der neuen Adresse legte eine zweite an; die alte bliebe stehen und würde
     * weiter bespielt.
     *
     * Eine unbekannte Adresse läuft folgenlos durch – wer nie im Auftragsbuch
     * stand, hat auch nichts fortzuschreiben (Muster von `scheduleRemoval()`).
     */
    public function changeEmail(string $previousEmail, string $newEmail): ?MarketingContact
    {
        $contact = $this->finde($previousEmail);

        if (null === $contact) {
            return null;
        }

        // Beide Seiten kleingeschrieben vergleichen: `setEmail()` normalisiert,
        // ein reiner Schreibweisenwechsel ist deshalb keine Änderung.
        $normalised = mb_strtolower(trim($newEmail));

        if ($contact->getEmail() === $normalised) {
            return $contact;
        }

        $collision = $this->finde($normalised);

        if (null !== $collision) {
            // ⚠ Unter der neuen Adresse steht bereits eine Zeile – etwa, weil
            // derselbe Mensch sich damit schon auf einer Warteliste eingetragen
            // hat. Ein `setEmail()` liefe hier in den Unique-Index auf `email`,
            // und der Nutzer bekäme beim Bestätigen seiner Adressänderung einen
            // 500er zu sehen. Das ist der schlechteste denkbare Ausgang: Die
            // Änderung ist zu diesem Zeitpunkt bereits am Konto wirksam.
            //
            // Maßgeblich bleibt deshalb die vorhandene Zeile (EC-01: eine
            // Adresse, ein Kontakt), und die alte wird zum Löschauftrag – sonst
            // bespielte der nächste Lauf weiter eine aufgegebene Adresse.
            // Ohne auslösende Quelle, und das ist hier richtig: Der Nutzer
            // trägt zu diesem Zeitpunkt bereits die neue Adresse, taucht unter
            // der alten also gar nicht mehr auf. Steht dort noch eine
            // Warteliste mit gültiger Einwilligung, erkennt `scheduleRemoval()`
            // sie und lässt den Kontakt stehen (BF-84).
            $this->scheduleRemoval($contact->getEmail());

            $collision
                ->setSyncState(MarketingSyncState::PENDING)
                ->setLastError(null)
                ->setAttempts(0);

            return $collision;
        }

        // ⚠ Die Umschreibung läuft auch bei einer **gesperrten** Zeile. Die
        // Sperre gehört zum Menschen, nicht zur Adresse; bliebe sie unter der
        // alten stehen, legte die nächste Einwilligung unter der neuen Adresse
        // eine zweite Zeile an und die Abmeldung wäre verloren (AK-12).
        // Übertragen wird trotzdem nichts: `findOpenForSync()` hält gesperrte
        // Zeilen unabhängig vom Zustand draußen.
        $contact
            ->setEmail($normalised)
            ->setSyncState(MarketingSyncState::PENDING)
            ->setLastError(null)
            ->setAttempts(0);

        return $contact;
    }

    /**
     * Stellt den Auftrag, den Kontakt bei Brevo zu entfernen.
     *
     * ⚠ **Muss laufen, BEVOR die Quelle gelöscht wird** (AK-13, AK-14). Danach
     * gibt es niemanden mehr, der den Auftrag stellen könnte – und die Adresse
     * bliebe für immer in Brevo stehen. Dass diese Tabelle keinen
     * Fremdschlüssel hat, ist genau dafür da: Der Auftrag überlebt seine
     * Quelle.
     *
     * ⚠ Die lokale Löschung hängt **nicht** vom Erfolg bei Brevo ab (AK-16).
     * Sie läuft durch; der Rest steht im Auftragsbuch.
     *
     * Eine unbekannte Adresse läuft folgenlos durch – wer nie übertragen
     * wurde, hat dort nichts zu löschen (EC-04).
     *
     * ⚠ **BF-84: Der Löschauftrag gilt der Adresse, ausgelöst hat ihn aber
     * eine einzelne Quelle.** Das Auftragsbuch führt bewusst **eine Zeile je
     * Adresse** – das löst EC-01 beim Eintragen und kippt beim Austragen:
     * Steht dieselbe Adresse auf einer Warteliste **und** an einem Konto
     * (ein Restaurantbesitzer mit persönlichem Konto), löschte der Widerruf
     * der Warteliste vorher auch den Kontakt des Kontos – dessen Einwilligung
     * niemand zurückgenommen hatte, ohne jede Fehleranzeige.
     *
     * Deshalb wird gelöscht **nur, wenn keine andere Quelle mehr eine gültige
     * Einwilligung trägt**. Andernfalls wandert die Zeile auf die verbleibende
     * Quelle: Herkunft, Namen und Vertriebsstatus werden von dort neu
     * abgeleitet, der Zustand geht auf `pending`, und der nächste Lauf schreibt
     * den Kontakt bei Brevo fort statt ihn zu entfernen.
     *
     * @param object|null $ausloesendeQuelle Der Eintrag bzw. das Konto, das
     *                                       gerade gelöscht wird. Er zählt
     *                                       nicht mehr mit – zum Zeitpunkt des
     *                                       Aufrufs steht er noch in der
     *                                       Datenbank, ist aber im Begriff zu
     *                                       verschwinden.
     */
    public function scheduleRemoval(string $email, ?object $ausloesendeQuelle = null): ?MarketingContact
    {
        $contact = $this->finde($email);

        if (null === $contact) {
            return null;
        }

        $verbleibende = $this->aktiveQuellen($email, $ausloesendeQuelle);

        if ([] !== $verbleibende) {
            return $this->recordSource($verbleibende[0]);
        }

        $contact
            ->setSyncState(MarketingSyncState::REMOVAL_PENDING)
            ->setLastError(null)
            ->setAttempts(0);

        return $contact;
    }

    /**
     * Quellen unter dieser Adresse, die eine **gültige** Werbe-Einwilligung
     * tragen – also bestätigt bzw. verifiziert sind und nicht widerrufen haben.
     *
     * Dieselben Bedingungen wie in `recordWaitlistEntry()`/`recordUser()`; sie
     * stehen hier ein zweites Mal, weil dort eine Quelle **eingetragen** und
     * hier gezählt wird, ob eine übrig bleibt.
     *
     * @return list<PartnerWaitlistEntry|OrganisationWaitlistEntry|User>
     */
    private function aktiveQuellen(string $email, ?object $ausser = null): array
    {
        $aktive = [];

        foreach ($this->sourcesFor($email) as $quelle) {
            if ($quelle === $ausser) {
                continue;
            }

            if (!$quelle->hasMarketingConsent()) {
                continue;
            }

            // ⚠ BF-89: bei Wartelisten `hasSelfConfirmed()`, nicht
            // `isConfirmed()` — sonst hielte ein bloß verwaltungsseitig
            // weitergesetzter Eintrag den Kontakt am Leben.
            $bestaetigt = $quelle instanceof User
                ? $quelle->isVerified()
                : $quelle->hasSelfConfirmed();

            if ($bestaetigt) {
                $aktive[] = $quelle;
            }
        }

        return $aktive;
    }

    /**
     * Trägt eine beliebige Quelle ein – die Fallunterscheidung an einer Stelle.
     */
    private function recordSource(object $quelle): ?MarketingContact
    {
        if ($quelle instanceof User) {
            return $this->recordUser($quelle);
        }

        if ($quelle instanceof WaitlistEntryInterface) {
            return $this->recordWaitlistEntry($quelle);
        }

        return null;
    }

    /**
     * Sperrt eine Adresse, weil Brevo eine Abmeldung, einen harten Bounce oder
     * eine Beschwerde gemeldet hat (AK-11).
     *
     * Zwei Wirkungen, und beide sind nötig:
     *
     * 1. **Die Zeile im Auftragsbuch wird zur Sperre** (`revokedAt`). Ohne sie
     *    trüge der nächste Lauf dieselbe Adresse erneut ein (AK-12) – eine
     *    Abmeldung, von der nur Brevo weiß, wäre nach 5 Minuten überschrieben.
     * 2. **Die Einwilligung an der Quelle wird gelöscht.** Sonst stünde im
     *    Wartelisten-Eintrag bzw. am Konto weiterhin eine Zustimmung, die der
     *    Mensch gerade zurückgenommen hat – und der Datenexport (AK-44) meldete
     *    ihm eine Einwilligung, die es nicht mehr gibt.
     *
     * ⚠ **Es wird keine Zeile angelegt, wenn keine existiert.** Eine Sperre auf
     * Vorrat für eine Adresse, die nie eingewilligt hat, wäre ein Datensatz
     * über einen Menschen, der mit uns nichts zu tun hat.
     *
     * ⚠ **Der Zustand bleibt, wie er ist.** Insbesondere wird aus einer Sperre
     * kein Löschauftrag: Der Werbewiderspruch lässt die Anmeldung bestehen
     * (Entscheidung 7), und der Kontakt darf in Brevo stehen bleiben – dort ist
     * er nach der Abmeldung ohnehin auf „blacklisted" und bekommt nichts mehr.
     * Ihn zu löschen nähme Brevo die eigene Abmelde-Erinnerung weg.
     *
     * ⚠ **BF-84b: Ohne Zeile im Auftragsbuch geschieht gar nichts.** Brevo
     * meldet `contactDeleted` **auch bei einer Löschung über die API** – also
     * als Echo unseres eigenen `delete()`-Aufrufs. Vorher leerte dieses Echo
     * `marketing_consent_at` an allen Quellen; damit verschwand ein
     * Einwilligungsnachweis nach Art. 7 Abs. 1 DSGVO, den niemand widerrufen
     * hatte. Ist die Zeile weg, war die Löschung unsere eigene – dann gibt es
     * nichts zu sperren und erst recht nichts zu entwerten.
     *
     * @param bool $einwilligungZurueckziehen Nur eine **Willenserklärung des
     *                                        Empfängers** (Abmeldung) oder ein
     *                                        Zustellproblem (Bounce, Beschwerde)
     *                                        entwertet die Einwilligung an der
     *                                        Quelle. Eine gelöschte Karteikarte
     *                                        bei Brevo tut das nicht — sie sagt
     *                                        nichts über den Willen des
     *                                        Menschen aus.
     */
    public function blockByEmail(string $email, bool $einwilligungZurueckziehen = true): void
    {
        $contact = $this->finde($email);

        if (null === $contact) {
            return;
        }

        $contact->setRevokedAt(new \DateTimeImmutable());

        if (!$einwilligungZurueckziehen) {
            return;
        }

        foreach ($this->sourcesFor($email) as $source) {
            $source->setMarketingConsentAt(null);
        }
    }

    /**
     * Alle Stellen, an denen eine Werbe-Einwilligung zu dieser Adresse stehen
     * kann. Dieselbe Adresse kann in mehreren stehen (EC-01) – deshalb werden
     * alle drei durchsucht und nicht die erste gewinnt.
     *
     * @return list<PartnerWaitlistEntry|OrganisationWaitlistEntry|User>
     */
    private function sourcesFor(string $email): array
    {
        $normalised = mb_strtolower(trim($email));
        $sources = [];

        foreach ([PartnerWaitlistEntry::class, OrganisationWaitlistEntry::class, User::class] as $class) {
            foreach ($this->entityManager->getRepository($class)->findBy(['email' => $normalised]) as $found) {
                $sources[] = $found;
            }
        }

        return $sources;
    }

    /**
     * Herkunft einer Wartelisten-Anmeldung.
     *
     * Die Zuordnung steht hier und nicht im Interface: Der Typ einer
     * Organisation ist typspezifisch, und das Interface hält Typspezifisches
     * bewusst heraus.
     */
    private function originOf(WaitlistEntryInterface $entry): MarketingOrigin
    {
        if ($entry instanceof PartnerWaitlistEntry) {
            return MarketingOrigin::PARTNER;
        }

        if ($entry instanceof OrganisationWaitlistEntry) {
            $type = $entry->getType();

            return null === $type
                ? MarketingOrigin::COMPANY
                : MarketingOrigin::fromOrganisationType($type);
        }

        // Ein dritter Wartelisten-Typ müsste hier eingetragen werden. Bis
        // dahin ist ACCOUNT die harmloseste Annahme: kein Vertriebskanal.
        return MarketingOrigin::ACCOUNT;
    }

    /**
     * Bliebe die Zeile auch mit dieser Einwilligung gesperrt?
     *
     * Entscheidung 8 des Entwurfs: AK-12 („nicht erneut anlegen") und AK-45
     * („Adresse wieder frei") stehen sonst gegeneinander. Maßgeblich ist der
     * jüngere Zeitpunkt.
     */
    private function wouldStayBlocked(MarketingContact $contact, \DateTimeImmutable $consentAt): bool
    {
        $revokedAt = $contact->getRevokedAt();

        return null !== $revokedAt && $consentAt <= $revokedAt;
    }
}
