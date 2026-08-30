<?php

namespace App\Marketing;

use App\Entity\MarketingContact;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Bildet eine Zeile des Auftragsbuchs auf den Rumpf ab, der an Brevo geht.
 *
 * Diese Klasse ist die **Datenschutz-Grenze** von Feature 04: Was hier nicht
 * hineingeschrieben wird, verlässt das Projekt nicht. Sie steht deshalb
 * getrennt von `BrevoContactClient` – der Dienst weiß, *wie* man ruft, diese
 * Abbildung weiß, *was* mitgeht. Beides zu vermischen hieße, die Negativliste
 * in einer Methode zu verstecken, deren Zweck der HTTP-Aufruf ist; geprüft
 * wird sie aber am tatsächlichen Rumpf, und der entsteht hier.
 *
 * **Was mitgeht** (AK-07, AK-28): die Adresse, die Datensatz-Kennung als
 * `ext_id` und genau fünf Attribute – `CONTACT_NAME`, `ORGANISATION`,
 * `LOCALE`, `ORIGIN`, `FUNNEL_STATUS`.
 *
 * ⚠ **Die Negativliste ist der Zweck dieser Klasse, kein Nebensatz.** Nicht mit
 * gehen: die **Freitextnachricht** aus beiden Wartelisten, die
 * **Telefonnummer**, der **Ort**, die **Herkunftsquelle** (`source`/UTM), jede
 * **IP-Adresse** und jeder **Token**. Keines dieser Felder wird hier gelesen –
 * die meisten führt `MarketingContact` gar nicht erst. Das ist Absicht und
 * bleibt so (AK-28, AK-29).
 */
final class MarketingPayloadMapper
{
    public function __construct(
        /**
         * Kennung der einen Marketing-Liste (Entscheidung 3).
         *
         * ⚠ **String und möglicherweise `''`.** Der Parameter ist in
         * `config/services.yaml` bewusst `string:` und nicht `int:` – der
         * `int:`-Prozessor wirft bei leerem Wert („Non-numeric env var") und
         * sprengte damit den Container-Build im Regelfall „Funktion aus".
         * Die Umwandlung nach `int` geschieht deshalb hier, **nachdem** auf
         * leer geprüft wurde. Leer heißt: keine Listenzuordnung, der Schlüssel
         * `listIds` fehlt dann ganz im Rumpf.
         */
        #[Autowire('%app.brevo_list_id%')]
        private readonly string $listId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toBrevoPayload(MarketingContact $contact): array
    {
        $id = $contact->getId();

        if (null === $id) {
            // Ohne Kennung gäbe es kein `ext_id`, und damit genau den Zustand,
            // den Entscheidung 4 ausschließt: Der Kontakt wäre nur noch über
            // seine Adresse auffindbar, und die nächste Adressänderung ließe
            // einen zweiten Kontakt entstehen (EC-02). Ein leeres `ext_id`
            // still mitzuschicken wäre schlimmer als ein lauter Abbruch.
            throw new \LogicException('MarketingContact ohne Kennung lässt sich nicht auf einen Brevo-Rumpf abbilden – ext_id wäre leer.');
        }

        $payload = [
            'email' => $contact->getEmail(),

            // Adressiert wird über die eigene Datensatz-Kennung, nicht über die
            // Adresse: Sie ist das einzige Feld, das sich ändern kann
            // (Entscheidung 4, EC-02).
            'ext_id' => (string) $id,

            // Immer `true`. `POST /contacts` legt damit an **oder** schreibt
            // fort; Idempotenz (AK-20) und Dublettenfreiheit (AK-25) sind so
            // strukturell gegeben, ohne vorher zu fragen, ob es den Kontakt
            // schon gibt. Eine solche Vorabfrage wäre ein zweiter Aufruf mit
            // eigenem Fehlerfall – und zwischen Frage und Antwort eine Lücke.
            'updateEnabled' => true,

            // ⚠ Genau fünf Attribute, und die Liste ist abschließend (AK-07).
            //
            // Was hier nicht hineingehört und auch später nicht hineingehört:
            // die **Freitextnachricht** aus beiden Wartelisten (AK-29). Auf
            // einer Barrierefreiheitsplattform kann dort alles stehen – auch
            // eine Gesundheitsangabe und damit eine besondere Kategorie nach
            // Art. 9 DSGVO. `MarketingContact` führt das Feld gar nicht erst;
            // wer es hier ergänzen will, müsste es zuerst dort einführen, und
            // genau diese Hürde ist der Schutz. Ebenso wenig gehen mit:
            // Telefonnummer, Ort, `source`/UTM, jede IP-Adresse, jeder Token.
            'attributes' => [
                'CONTACT_NAME' => $contact->getContactName() ?? '',
                'ORGANISATION' => $contact->getOrganisationName() ?? '',
                'LOCALE' => $contact->getLocale(),

                // ⚠ `ORIGIN` bezeichnet die **Rolle im Vertrieb** – Partner,
                // Gemeinde, Unternehmen, Verein, Nutzerkonto (AK-30). Es sagt
                // ausdrücklich **nicht**, ob jemand selbst von einer
                // Behinderung betroffen ist. Auf dieser Plattform ist das der
                // Unterschied zwischen einem Vertriebsmerkmal und einer
                // besonderen Kategorie nach Art. 9 DSGVO. Wer dieses Attribut
                // später um einen Wert erweitert, prüft diesen Satz zuerst.
                'ORIGIN' => $contact->getOrigin()->brevoValue(),

                // Bei Nutzerkonten ist der Vertriebsstatus `null`. Er geht
                // trotzdem mit – als leerer Wert, statt weggelassen zu werden.
                //
                // Brevo **überschreibt** ein mitgeschicktes Attribut und lässt
                // ein weggelassenes unangetastet. Weglassen hieße hier: Was in
                // Brevo steht, bleibt stehen. Für `FUNNEL_STATUS` wäre das
                // falsch – das Auftragsbuch ist für dieses Merkmal die einzige
                // Quelle, Brevo hat keine andere. Ein Rest aus einem früheren
                // Stand (etwa `converted` aus einem Handimport oder aus einer
                // Zeile, die zwischenzeitlich ihre Herkunft gewechselt hat)
                // bliebe sonst hängen und schlösse den Kontakt dauerhaft aus
                // einer Kampagne aus, die genau darauf filtert (AK-08).
                // Zugleich wäre die Übertragung nicht mehr idempotent: Das
                // Ergebnis hinge davon ab, was vorher dort stand (AK-20).
                //
                // Das ist bewusst das Gegenteil der Behandlung von
                // `emailBlacklisted` weiter unten. Die Regel dahinter ist
                // dieselbe: **Wo wir die Quelle sind, schreiben wir; wo Brevo
                // die Quelle ist, fassen wir nichts an.**
                'FUNNEL_STATUS' => $contact->getFunnelStatus()?->value ?? '',
            ],
        ];

        // Die Listenzuordnung nur, wenn eine Liste konfiguriert ist. Ein
        // `listIds: [0]` aus einem leeren Wert wäre eine erfundene Liste und
        // ließe den Aufruf mit einem Fehler enden, dessen Ursache in der
        // Konfiguration liegt und nicht im Kontakt.
        $listId = trim($this->listId);

        if ('' !== $listId) {
            $payload['listIds'] = [(int) $listId];
        }

        // ⚠ **`emailBlacklisted` steht NIE im Rumpf** (EC-05).
        //
        // Brevo setzt das Feld ausschließlich dann, wenn es mitgeschickt wird.
        // Fehlt es, bleibt eine dort bestehende Abmeldung unangetastet – genau
        // das braucht EC-05, wenn der Abgleich auf einen Kontakt aus einem
        // früheren Handimport trifft. Wer es „vorsichtshalber" auf `false`
        // setzt, hebt jede Abmeldung auf, die in Brevo schon steht, und
        // schickt Werbung an Menschen, die widersprochen haben.
        //
        // Die lokale Sperre (`MarketingContact::isBlocked()`) ersetzt das
        // nicht: Sie kennt nur Abmeldungen, die uns über den Webhook erreicht
        // haben. Eine Abmeldung, die vor dem Webhook in Brevo entstand, kennt
        // nur Brevo – und deshalb bleibt sie dort unberührt.
        return $payload;
    }
}
