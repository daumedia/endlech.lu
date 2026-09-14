<?php

declare(strict_types=1);

namespace App\Usage;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Prüft und kürzt einen Zählaufruf, bevor er Umami erreicht (Feature 11).
 *
 * ⚠ **Das ist die einzige Grenze für den Inhalt** (Entwurf, „Zugriffsregeln"). Die Tracker-Optionen
 * — ohne Abfrage, ohne Anker, nur eine Domain, „Do Not Track" — gelten nur für ehrliche Browser;
 * jeder kann einen Zählaufruf selbst bauen. Deshalb wiederholt diese Klasse jede Regel, die den
 * Inhalt betrifft, nach der Projektkonvention „Die Prüfung gehört dorthin, wo der Wert hereinkommt".
 *
 * ⚠ **Ausgabe ist eine Positivliste.** Übernommen werden nur die Felder, die hier ausdrücklich
 * gebildet werden. Ein neues Feld, das der Tracker nach einem Update mitschickt, erreicht Umami erst,
 * wenn es hier eingetragen ist — lieber eine Zahl zu wenig als eine Personenangabe zu viel.
 *
 * Reiner Dienst: keine Anfrage, kein Speicher, kein Protokoll.
 */
final readonly class CollectPayloadNormalizer
{
    /** Seitentitel werden gekürzt, nicht abgewiesen — ein langer Ideentitel ist kein Angriff. */
    public const int MAX_TITLE = 200;

    /** Pfade, von denen nie ein Zählaufruf kommen darf (AK-06). */
    private const string AUSGENOMMENE_PFADE = '#^/[a-z]{2}/(admin|profile)(/|$)#';

    /**
     * Ein Token in Pfadform: die Bestätigungs-, Abmelde- und Passwort-Links tragen 64 Hex-Zeichen
     * (AK-07). Geprüft wird das Muster, nicht die Route — ein erfundener Pfad mit Token soll ebenso
     * wenig ankommen wie ein echter.
     */
    private const string TOKEN = '#[a-f0-9]{64}#i';

    private string $hostname;

    public function __construct(
        private UsageEventCatalogue $catalogue,
        #[Autowire('%app.umami_website_id%')]
        private string $websiteId,
        #[Autowire('%app.canonical_base_url%')]
        string $canonicalBaseUrl,
    ) {
        $this->hostname = (string) parse_url($canonicalBaseUrl, \PHP_URL_HOST);
    }

    /**
     * @param array<mixed> $body entschlüsselter Rumpf des Zählaufrufs: `{type, payload}`
     *
     * @return array{type: string, payload: array<string, mixed>}
     *
     * @throws InvalidCollectPayload
     */
    public function normalize(array $body): array
    {
        if ('event' !== ($body['type'] ?? null)) {
            throw new InvalidCollectPayload('type');
        }

        $eingang = $body['payload'] ?? null;
        if (!\is_array($eingang)) {
            throw new InvalidCollectPayload('payload');
        }

        if ('' === $this->websiteId || ($eingang['website'] ?? null) !== $this->websiteId) {
            throw new InvalidCollectPayload('website');
        }

        // AK-09: exakt die Hauptadresse — `www.` und jede andere Domain fallen heraus.
        if (($eingang['hostname'] ?? null) !== $this->hostname) {
            throw new InvalidCollectPayload('hostname');
        }

        $ausgabe = [
            'website' => $this->websiteId,
            'hostname' => $this->hostname,
            'url' => $this->pfad($eingang['url'] ?? null),
        ];

        $herkunft = $this->herkunft($eingang['referrer'] ?? null);
        if (null !== $herkunft) {
            $ausgabe['referrer'] = $herkunft;
        }

        if (\is_string($eingang['title'] ?? null)) {
            $ausgabe['title'] = mb_substr($eingang['title'], 0, self::MAX_TITLE);
        }

        if (\is_string($eingang['screen'] ?? null) && 1 === preg_match('#^\d{1,5}x\d{1,5}$#', $eingang['screen'])) {
            $ausgabe['screen'] = $eingang['screen'];
        }

        if (\is_string($eingang['language'] ?? null) && 1 === preg_match('#^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})?$#', $eingang['language'])) {
            $ausgabe['language'] = $eingang['language'];
        }

        $this->ereignis($eingang, $ausgabe);

        // `id` (Umamis Nutzerkennung) und `tag` werden nie übernommen (AK-21) — sie stehen in
        // keiner der Zeilen oben.
        // ⚠ Ebenso nie `ip`, `userAgent`, `timestamp`, `browser`, `os` und `device`: Umami 3.3.1 nimmt diese
        // Felder im Zählaufruf an und stellt sie über das, was es selbst ermittelt. Vom Client übernommen, legte
        // jeder Besucher sein Land, seine Sitzung und seinen Zeitpunkt selbst fest (AK-04, AK-40). Die
        // Besucheradresse setzt erst `UmamiForwarder` ein — aus der Anfrage, nicht aus dem Rumpf.
        return ['type' => 'event', 'payload' => $ausgabe];
    }

    /** Nur der Pfad; Abfrage und Anker entfallen (AK-03). */
    private function pfad(mixed $url): string
    {
        if (!\is_string($url) || '' === $url) {
            throw new InvalidCollectPayload('url');
        }

        $teile = parse_url($url);
        if (false === $teile) {
            throw new InvalidCollectPayload('url');
        }

        // Eine absolute Adresse muss auf die Hauptadresse zeigen.
        if (isset($teile['host']) && $teile['host'] !== $this->hostname) {
            throw new InvalidCollectPayload('url');
        }

        $pfad = $teile['path'] ?? '/';
        if (!str_starts_with($pfad, '/')) {
            throw new InvalidCollectPayload('url');
        }

        if (1 === preg_match(self::AUSGENOMMENE_PFADE, $pfad) || 1 === preg_match(self::TOKEN, $pfad)) {
            throw new InvalidCollectPayload('url');
        }

        return $pfad;
    }

    /** Fremde Herkunft nur als Domain (AK-02); eigene Herkunft und alles Unklare entfällt. */
    private function herkunft(mixed $referrer): ?string
    {
        if (!\is_string($referrer) || '' === $referrer) {
            return null;
        }

        $teile = parse_url($referrer);
        $host = \is_array($teile) ? ($teile['host'] ?? null) : null;
        $schema = \is_array($teile) ? strtolower($teile['scheme'] ?? '') : '';

        if (!\is_string($host) || !\in_array($schema, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($host);
        if ($host === $this->hostname || $host === 'www.'.$this->hostname) {
            return null;
        }

        return 'https://'.$host.'/';
    }

    /**
     * @param array<mixed>         $eingang
     * @param array<string, mixed> $ausgabe
     */
    private function ereignis(array $eingang, array &$ausgabe): void
    {
        $name = $eingang['name'] ?? null;
        $daten = $eingang['data'] ?? null;

        if (null === $name) {
            // Ein Seitenaufruf trägt keine Ereignisdaten.
            if (null !== $daten) {
                throw new InvalidCollectPayload('data');
            }

            return;
        }

        if (!\is_string($name)) {
            throw new InvalidCollectPayload('name');
        }

        $daten ??= [];
        if (!\is_array($daten) || !$this->catalogue->isAllowed($name, $daten)) {
            throw new InvalidCollectPayload('event');
        }

        $ausgabe['name'] = $name;
        if ([] !== $daten) {
            $ausgabe['data'] = $daten;
        }
    }
}
