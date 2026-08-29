<?php

declare(strict_types=1);

namespace App\Marketing;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Der einzige Ort im Projekt, der Brevos Kontakt-Schnittstelle anspricht.
 *
 * Zwei Handlungen, mehr braucht Feature 04 nicht: einen Kontakt anlegen oder
 * fortschreiben (`upsert`) und einen Kontakt entfernen (`delete`). Kein SDK —
 * ein Aufruf in zwei Ausprägungen, das Muster steht seit `PublicTransportService`
 * im Projekt (Entscheidung 10 im Systemdesign).
 *
 * **Der Client kennt das Auftragsbuch nicht.** Er baut keinen Rumpf, er
 * entscheidet nicht, wer übertragen wird, und er fängt seine Fehler nicht ab —
 * er wirft `BrevoRequestFailed` und überlässt dem Sync-Dienst, ob das ein
 * erneuter Versuch wird. Was in den Rumpf darf und was nicht (AK-28, AK-29),
 * entscheidet `MarketingPayloadMapper`.
 *
 * ⚠ **Ausfall ist der Normalfall, nicht die Ausnahme.** Brevo antwortet
 * gelegentlich mit 429 oder gar nicht. Deshalb eigener `timeout` (siehe unten)
 * und ein Fehlerweg, der die Anmeldung eines Besuchers nie erreicht — kein
 * Anfrage-Ablauf ruft diese Klasse (AK-17, Entscheidung 1).
 *
 * ⚠ **Die Antwort wird bewusst nie gelesen.** Weder `toArray()` noch
 * `getContent()` — allein `getStatusCode()`. Brevos Fehlerrümpfe spiegeln die
 * übergebene E-Mail-Adresse zurück; was nicht eingelesen wird, kann auch nicht
 * versehentlich in einer Protokollzeile landen (AK-31).
 */
final class BrevoContactClient
{
    private const ENDPOINT = 'https://api.brevo.com/v3/contacts';

    /**
     * ⚠ **Pflichtangabe.** Ohne eigenen Wert greift der PHP-Standard
     * `default_socket_timeout` — auf dem Messsystem 60 Sekunden (BF-44 beim
     * Haltestellen-Dienst). Dort sind es 3 Sekunden, weil ein Besucher wartet;
     * hier läuft der Aufruf im Cron, deshalb großzügiger. `max_duration`
     * deckelt zusätzlich die Gesamtdauer inklusive Umleitungen.
     */
    private const TIMEOUT = 10;
    private const MAX_DURATION = 15;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.brevo_api_key%')]
        private readonly string $apiKey,
        /**
         * ⚠ **Absichtlich nicht ausgewertet** — siehe {@see isConfigured()}.
         * Die Listenzuordnung steckt als `listIds` bereits im übergebenen Rumpf;
         * dieser Client braucht die Kennung für keinen seiner beiden Aufrufe.
         */
        #[Autowire('%app.brevo_list_id%')]
        private readonly string $listId,
    ) {
    }

    /**
     * ⚠ **Nur der Schlüssel entscheidet, nicht die Listen-ID.**
     *
     * Die Versuchung ist, `app.brevo_list_id` mitzuprüfen — beides ist
     * Konfiguration, beides kann leer sein. Drei Gründe dagegen:
     *
     * 1. `config/services.yaml` erklärt den leeren Wert ausdrücklich zum
     *    **gültigen Zustand** („Leer = keine Listenzuordnung"). Ein Kontakt ohne
     *    Liste ist in Brevo ein vollwertiger Kontakt mit allen Attributen; nur
     *    die Kampagne erreicht ihn noch nicht. Eine unterstützte Einstellung zur
     *    Abschaltbedingung zu machen, hieße AK-47 auf einen Fall auszudehnen, den
     *    die Spezifikation dort nicht meint.
     * 2. `delete()` hat mit Listen nichts zu tun. Eine fehlende Listen-ID würde
     *    sonst **Löschungen blockieren** (AK-13, AK-16, EC-04) — der Widerruf ist
     *    die eine Handlung, die niemals an einer Nebeneinstellung scheitern darf.
     * 3. Der Schlüssel ist die Voraussetzung dafür, dass ein Aufruf überhaupt
     *    stattfinden kann. Die Listen-ID ist Inhalt, und Inhalt gehört zu
     *    `MarketingPayloadMapper`.
     */
    public function isConfigured(): bool
    {
        return '' !== $this->apiKey;
    }

    /**
     * Legt den Kontakt an oder schreibt ihn fort.
     *
     * ⚠ **Zwei Endpunkte, und das ist kein Umweg.** Brevos Schnittstelle
     * trennt hier sauber, und die Trennung entscheidet über EC-02:
     *
     * - `PUT /contacts/{ext_id}?identifierType=ext_id` adressiert den Kontakt
     *   über **unsere** Kennung. Nur auf diesem Weg lässt sich die Adresse
     *   ändern: Sie geht als Attribut `EMAIL` mit und überschreibt die alte.
     * - `POST /contacts` kennt **kein** `identifierType` — der Parameter
     *   existiert dort schlicht nicht. Ein Upsert per POST sucht den Kontakt
     *   über die übergebene Adresse. Bei einer **geänderten** Adresse findet er
     *   nichts, legt einen zweiten Kontakt an, und der alte bleibt mitsamt
     *   Kampagnenzustellung stehen. Genau das ist der Fehler, den Entscheidung 4
     *   ausschließen soll.
     *
     * Deshalb: erst der Aktualisierungsweg, und **nur bei 404** — der Kontakt
     * existiert dort noch nicht — der Anlegeweg. `updateEnabled: true` bleibt
     * beim Anlegen gesetzt, damit auch ein zeitgleicher zweiter Lauf keine
     * Dublette erzeugt (AK-20, AK-25).
     *
     * @param array<string, mixed> $payload fertig gebaut von MarketingPayloadMapper
     *
     * @throws BrevoRequestFailed
     */
    public function upsert(array $payload): void
    {
        $extId = (string) ($payload['ext_id'] ?? '');

        if ('' === $extId) {
            throw BrevoRequestFailed::missingExtId();
        }

        /** @var array<string, mixed> $attributes */
        $attributes = $payload['attributes'] ?? [];

        // Die Adresse reist auf dem Aktualisierungsweg als Attribut — so und
        // nur so zieht ein Adresswechsel im bestehenden Kontakt nach (EC-02).
        $attributes['EMAIL'] = $payload['email'] ?? '';

        $update = ['attributes' => $attributes];

        if (isset($payload['listIds'])) {
            $update['listIds'] = $payload['listIds'];
        }

        $status = $this->request(
            'PUT',
            self::ENDPOINT.'/'.rawurlencode($extId),
            'update',
            ['json' => $update, 'query' => ['identifierType' => 'ext_id']],
        );

        if ($this->isSuccess($status)) {
            return;
        }

        if (404 !== $status) {
            throw $this->gescheitert(BrevoRequestFailed::fromStatus($status), 'update');
        }

        // Noch kein Kontakt unter dieser Kennung: anlegen. Der Rumpf geht so
        // hinaus, wie der Mapper ihn gebaut hat — diese Klasse ergänzt kein
        // personenbezogenes Feld.
        $create = $payload;
        $create['updateEnabled'] = true;

        $status = $this->request('POST', self::ENDPOINT, 'create', ['json' => $create]);

        if (!$this->isSuccess($status)) {
            throw $this->gescheitert(BrevoRequestFailed::fromStatus($status), 'create');
        }
    }

    /**
     * Entfernt den Kontakt, adressiert über die eigene Kennung.
     *
     * ⚠ **404 gilt als Erfolg.** Der Kontakt ist dann bereits weg, und genau das
     * war das Ziel (EC-04: Widerruf eines Eintrags, der nie übertragen wurde).
     * Ein Fehler an dieser Stelle brächte den Auftrag ins Auftragsbuch zurück und
     * ließe ihn dort bis zum Versuchsdeckel weiterlaufen — für einen Zustand, der
     * bereits der gewünschte ist.
     *
     * @throws BrevoRequestFailed
     */
    public function delete(int $extId): void
    {
        $status = $this->request('DELETE', self::ENDPOINT.'/'.$extId, 'delete', [
            'query' => ['identifierType' => 'ext_id'],
        ]);

        // 404: bereits weg – und genau das war das Ziel.
        if (404 === $status || $this->isSuccess($status)) {
            return;
        }

        throw $this->gescheitert(BrevoRequestFailed::fromStatus($status), 'delete');
    }

    private function isSuccess(int $status): bool
    {
        return $status >= 200 && $status < 300;
    }

    /**
     * Führt den Aufruf aus und gibt den Statuscode zurück.
     *
     * Wirft **nur** bei einem Transportfehler; die Auswertung des Statuscodes
     * bleibt beim Aufrufer, weil „404" je nach Handlung Erfolg (löschen) oder
     * Zwischenschritt (anlegen statt aktualisieren) bedeutet.
     *
     * @param array<string, mixed> $options
     *
     * @throws BrevoRequestFailed
     */
    private function request(string $method, string $url, string $operation, array $options): int
    {
        if (!$this->isConfigured()) {
            // Bewusst ohne Protokollzeile: Der Regelfall „Funktion aus" wird vom
            // Sync-Dienst über isConfigured() abgefangen und erreicht diese Stelle
            // nie (AK-47). Kommt sie doch hierher, ist es ein Programmierfehler —
            // und dann wären es bis zu `app.brevo_sync_batch` gleichlautende
            // Zeilen je Lauf, plus je ein Sentry-Ereignis. Sichtbar wird der
            // Zustand über `last_error` im Auftragsbuch und damit in der
            // Verwaltung (AK-18, AK-26); das ist der dafür vorgesehene Kanal.
            throw BrevoRequestFailed::notConfigured();
        }

        try {
            return $this->httpClient->request($method, $url, array_merge($options, [
                'timeout' => self::TIMEOUT,
                'max_duration' => self::MAX_DURATION,
                'headers' => [
                    'api-key' => $this->apiKey,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
            ]))->getStatusCode();
        } catch (\Throwable $e) {
            throw $this->gescheitert(BrevoRequestFailed::fromThrowable($e), $operation);
        }
    }

    /**
     * Protokolliert den Fehlversuch und gibt die Ausnahme zum Werfen zurück.
     *
     * ⚠ **Hier steht die Umsetzung von AK-31, und sie ist knapp gehalten, weil
     * jede zusätzliche Angabe ein Leck wäre.** In die Zeile gehen genau zwei
     * Werte: welche der beiden Handlungen es war, und die Kurzform aus
     * `BrevoRequestFailed` — „HTTP 429" oder „TransportException". Nicht in die
     * Zeile gehen:
     *
     * - **die Fremdmeldung** (`$e->getMessage()` des HttpClient) — der Grund steht
     *   ausgeschrieben in `PublicTransportService`: sie trägt die vollständige
     *   URL. Hier reist der Schlüssel zwar als Kopfzeile und nicht in der URL,
     *   aber die Regel gilt unabhängig davon, wo er gerade steht;
     * - **die Ausnahme als Objekt** (Kontextschlüssel `exception`) — Monolog
     *   rendert dann die Kette samt `previous` und damit doch wieder den Wortlaut;
     * - **die E-Mail-Adresse** — weder vollständig noch maskiert. Der Rumpf wird
     *   nicht angefasst, die Antwort nicht gelesen; welcher Kontakt betroffen war,
     *   steht ohnehin im Auftragsbuch und dort gehört es hin.
     *
     * `SecretMaskingProcessor` fängt den Rest ab, was das Framework selbst
     * schreibt — auf ihn wird sich hier bewusst nicht verlassen.
     */
    private function gescheitert(BrevoRequestFailed $fehler, string $operation): BrevoRequestFailed
    {
        $this->logger->error('Brevo contact API error: {operation} failed ({reason})', [
            'operation' => $operation,
            'reason' => $fehler->getMessage(),
        ]);

        return $fehler;
    }
}
