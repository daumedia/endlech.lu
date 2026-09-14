<?php

declare(strict_types=1);

namespace App\Usage;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reicht einen geprüften Zählaufruf an die Umami-Domain weiter (Feature 11).
 *
 * ⚠⚠ **Die Umami-Domain führt zum VPS der Überwachung** (derselbe VPS wie Uptime Kuma). Sie steht im
 * öffentlichen DNS, aber der Weg von endlech.lu dorthin soll nirgends dokumentiert sein (AK-24, AK-42).
 * Sie erscheint deshalb in keinem Protokoll: Der HTTP-Client ist ein eigener Dienst
 * **ohne Logger** (`app.usage.umami_client` in `config/services.yaml`, `autoconfigure: false`), weil
 * der Kanal `http_client` in `prod` nicht ausgeschlossen ist und `fingers_crossed` bei einer Warnung
 * den ganzen Puffer samt Adresse schreibt. Geloggt wird bei einem Fehlschlag nur die
 * **Ausnahmeklasse** — Symfonys Transport-Ausnahmen tragen die vollständige Adresse im Text.
 * Dieselbe Lehre wie beim Puls (BE-01).
 *
 * ⚠⚠ **Die Besucheradresse geht als Feld `ip` im Zählaufruf mit, nicht als Kopfzeile** (Entwurf,
 * Entscheidung 18). Die Umami-Instanz steht hinter dem Proxy des Hosters und läuft mit ihren Vorgaben; ohne
 * `ip` nimmt sie die Adresse aus den Kopfzeilen dieses Proxys — die des Anwendungs-VPS, für jeden Besucher.
 * Nachgestellt am 2026-09-14 an Umami 3.3.1: ohne `ip` landeten zwei verschiedene Besucher gemeinsam in
 * Argentinien (die Adresse des Anwendungs-VPS liegt im 179er-Bereich) und in **einer** Sitzung; mit `ip` bekam
 * jeder sein Land und seine Sitzung, und Orts-Kopfzeilen wie `cf-ipcountry` blieben wirkungslos.
 *
 * ⚠ **Kopfzeilen sind eine Positivliste.** Würde die Weiterleitung `X-Forwarded-For` oder `cf-ipcountry` aus der
 * eingehenden Anfrage durchreichen, könnte ein Client Adresse und Land unterschieben. Ein vom Client im Rumpf
 * gesetztes `ip` hat `CollectPayloadNormalizer` schon entfernt.
 *
 * ⚠ **Zeitlimit und Unterbrecher sind Pflicht** (AK-37). Ohne eigenes Zeitlimit griffe
 * `default_socket_timeout` (im Bestand mit 60 s gemessen); ohne Unterbrecher hielte bei ausgefallenem
 * VPS jeder Zählaufruf einen PHP-Prozess bis zum Zeitlimit fest.
 *
 * ⚠⚠ **Und beides reicht nicht ohne den einen Platz** (BF-149). Der Unterbrecher greift erst, wenn ein
 * Aufruf gescheitert ist — bis dahin prüfte jede gleichzeitige Anfrage einen leeren Unterbrecher und
 * wartete selbst bis zum Zeitlimit. Gemessen gegen einen Eingang, der nie antwortet: sechs gleichzeitige
 * Zählaufrufe je 2,1–3,7 s, und die Restaurantliste wartete im selben Augenblick 1,8 s auf freie
 * PHP-Prozesse. Deshalb läuft **höchstens eine** Weiterleitung zur Zeit; wer den Platz nicht binnen
 * {@see self::PLATZ_WARTEN_MS} bekommt, wird nicht gezählt, statt zu warten. Im Normalbetrieb dauert ein
 * Aufruf Millisekunden, und der nächste bekommt den Platz; hängt Umami, wartet genau einer — das ist
 * Entscheidung 7 in `design.md` („ein hängender Aufruf je Minute"). Die Sperre ist eine Dateisperre:
 * FrankenPHP bedient alle Anfragen eines Containers aus einem Prozess, und `flock` trennt dort auch
 * Threads, weil jede Sperre die Datei selbst öffnet.
 */
final readonly class UmamiForwarder
{
    public const int TIMEOUT = 2;
    public const int MAX_DURATION = 3;
    public const int BREAKER_SECONDS = 60;

    /** So lange wartet ein Zählaufruf höchstens auf den Platz, bevor er aufgibt (BF-149). */
    public const int PLATZ_WARTEN_MS = 100;
    private const int PLATZ_TAKT_MS = 10;
    private const string PLATZ_SPERRE = 'umami-weiterleitung';

    private const int MAX_USER_AGENT = 512;
    private const int MAX_CACHE_TOKEN = 2048;
    private const int MAX_ANTWORT = 4096;
    private const string BREAKER_KEY = 'umami_unterbrecher';

    private string $upstream;

    public function __construct(
        #[Autowire(service: 'app.usage.umami_client')]
        private HttpClientInterface $client,
        #[Autowire(service: 'cache.usage')]
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
        private LockFactory $sperren,
        #[Autowire('%app.umami_upstream%')]
        string $upstream,
    ) {
        $this->upstream = rtrim($upstream, '/');
    }

    /**
     * @param array{type: string, payload: array<string, mixed>} $normalized Ausgabe von CollectPayloadNormalizer
     */
    public function forward(array $normalized, ?string $clientIp, ?string $userAgent, ?string $cacheToken): ForwardResult
    {
        if ('' === $this->upstream) {
            return ForwardResult::notForwarded();
        }

        $unterbrecher = $this->cache->getItem(self::BREAKER_KEY);
        if ($unterbrecher->isHit()) {
            return ForwardResult::notForwarded();
        }

        $kopfzeilen = ['Content-Type' => 'application/json'];
        if (null !== $userAgent && '' !== $userAgent) {
            $kopfzeilen['User-Agent'] = mb_substr($userAgent, 0, self::MAX_USER_AGENT);
        }
        if (null !== $cacheToken && \strlen($cacheToken) <= self::MAX_CACHE_TOKEN && 1 === preg_match('#^[A-Za-z0-9._\-+/=]+$#', $cacheToken)) {
            $kopfzeilen['x-umami-cache'] = $cacheToken;
        }
        if (null !== $clientIp && false !== filter_var($clientIp, \FILTER_VALIDATE_IP)) {
            $normalized['payload']['ip'] = $clientIp;
        }

        try {
            $platz = $this->platzBelegen();
        } catch (\Throwable $fehler) {
            // ⚠ BF-152: Ein Speicher, der nicht nur „belegt" meldet, sondern scheitert (Sperrdatei nicht zu
            // öffnen, Temp-Verzeichnis voll), wirft hier. Ungefangen wurde daraus ein 500er für jeden Zählaufruf,
            // und Sentry meldete jeden einzelnen. Behandelt wie jeder andere Ausfall: Klasse loggen, Unterbrecher
            // setzen — Letzteres begrenzt die Warnung auf eine je Minute statt eine je Aufruf.
            $this->logger->warning('Nutzungsmessung: Sperre der Weiterleitung nicht verfügbar, Unterbrecher gesetzt (Feature 11).', [
                'ausnahme' => $fehler::class,
            ]);
            $this->unterbrechen($unterbrecher);

            return ForwardResult::notForwarded();
        }
        if (null === $platz) {
            return ForwardResult::notForwarded();
        }

        try {
            // Wer auf den Platz gewartet hat, kommt womöglich nach einem Fehlschlag an die Reihe.
            if ($this->cache->getItem(self::BREAKER_KEY)->isHit()) {
                return ForwardResult::notForwarded();
            }

            $antwort = $this->client->request('POST', $this->upstream.'/api/send', [
                'headers' => $kopfzeilen,
                'json' => $normalized,
                'timeout' => self::TIMEOUT,
                'max_duration' => self::MAX_DURATION,
                // ⚠ Gewöhnliche Zertifikatsprüfung (Entwurf, Entscheidung 5). Keine Schlüsselbindung: Das Zertifikat
                // des Proxys wechselt bei jeder Erneuerung, eine Bindung bräche dann still. Und nie `verify_peer`
                // abschalten — sonst ginge die Besucheradresse an jeden, der sich dazwischenstellt.
            ]);
            $status = $antwort->getStatusCode();
            $rumpf = $antwort->getContent(false);
        } catch (\Throwable $fehler) {
            // Nur die Klasse, nie der Text: siehe Klassenkommentar.
            $this->logger->warning('Nutzungsmessung: Umami nicht erreichbar, Unterbrecher gesetzt (Feature 11).', [
                'ausnahme' => $fehler::class,
            ]);
            $this->unterbrechen($unterbrecher);

            return ForwardResult::notForwarded();
        } finally {
            $this->platzFreigeben($platz);
        }

        if ($status >= 500) {
            $this->logger->warning('Nutzungsmessung: Umami antwortete mit einem Serverfehler, Unterbrecher gesetzt (Feature 11).', [
                'status' => $status,
            ]);
            $this->unterbrechen($unterbrecher);

            return ForwardResult::notForwarded();
        }

        if ($status < 200 || $status >= 300 || \strlen($rumpf) > self::MAX_ANTWORT || !\is_array(json_decode($rumpf, true))) {
            return ForwardResult::forwarded('{}');
        }

        return ForwardResult::forwarded($rumpf);
    }

    /** Belegt den einen Platz für eine Weiterleitung oder gibt nach {@see self::PLATZ_WARTEN_MS} auf. */
    private function platzBelegen(): ?LockInterface
    {
        // ⚠ Ohne automatische Freigabe (BF-152): Scheitert `release()`, bleibt die Sperre als belegt markiert, und
        // der Destruktor versuchte es beim Verlassen von `forward()` erneut — die Ausnahme käme dann außerhalb jedes
        // `catch` heraus. Freigegeben wird ausdrücklich in `platzFreigeben()`; die Dateisperre selbst endet
        // spätestens, wenn der Prozess die Datei schließt.
        $platz = $this->sperren->createLock(self::PLATZ_SPERRE, self::MAX_DURATION + 1, false);
        for ($gewartet = 0; ; $gewartet += self::PLATZ_TAKT_MS) {
            if ($platz->acquire()) {
                return $platz;
            }
            if ($gewartet >= self::PLATZ_WARTEN_MS) {
                return null;
            }
            usleep(self::PLATZ_TAKT_MS * 1000);
        }
    }

    /** Gibt den Platz frei; ein Fehler dabei kostet nichts außer einer Warnung (BF-152). */
    private function platzFreigeben(LockInterface $platz): void
    {
        try {
            $platz->release();
        } catch (\Throwable $fehler) {
            $this->logger->warning('Nutzungsmessung: Sperre der Weiterleitung nicht freigegeben (Feature 11).', [
                'ausnahme' => $fehler::class,
            ]);
        }
    }

    private function unterbrechen(\Psr\Cache\CacheItemInterface $unterbrecher): void
    {
        $unterbrecher->set(true);
        $unterbrecher->expiresAfter(self::BREAKER_SECONDS);
        $this->cache->save($unterbrecher);
    }
}
