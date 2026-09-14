<?php

/*
 * QA Feature 11 · Überarbeitung 2026-09-14 — Entscheidung 5: gewöhnliche Zertifikatsprüfung ohne Schlüsselbindung.
 *
 * Übt die echte Klasse `UmamiForwarder` gegen Traefik mit drei Zertifikaten aus: von der Wegwerf-CA für den
 * richtigen Namen · von derselben CA für einen falschen Namen · selbst signiert. Der cURL-Client bekommt nur
 * die Wegwerf-CA als `cafile` (Grund: siehe Kopf von ueberarbeitung-umgebung.sh) — alles Übrige an Optionen
 * setzt die Weiterleitung selbst. Schaltete sie die Prüfung ab, gingen „falscher Name" und „selbst signiert"
 * durch; die Gegenprobe am Ende zeigt, dass genau das dann passiert.
 *
 * Aufruf: QA=<Prüfordner> php qa/11/ueberarbeitung-tls.php
 */

use App\Usage\UmamiForwarder;
use Psr\Log\AbstractLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

require __DIR__.'/../../vendor/autoload.php';

$qa = getenv('QA') ?: exit("QA=<Prüfordner> setzen\n");
$certs = $qa.'/certs';
$website = trim((string) file_get_contents($qa.'/website-id.txt'));
$upstream = 'https://localhost:39443';
$ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36';

$protokoll = [];
$logger = new class($protokoll) extends AbstractLogger {
    public function __construct(private array &$p)
    {
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->p[] = $level.' '.$message.' '.json_encode($context);
    }
};

$zertifikat = static function (string $name) use ($certs): void {
    copy("$certs/$name.crt", "$certs/aktiv.crt");
    copy("$certs/$name.key", "$certs/aktiv.key");
    exec('docker restart qa11u-traefik >/dev/null 2>&1');
    for ($i = 0; $i < 60; ++$i) {
        $c = curl_init('https://localhost:39443/api/heartbeat');
        curl_setopt_array($c, [\CURLOPT_RETURNTRANSFER => true, \CURLOPT_SSL_VERIFYPEER => false, \CURLOPT_SSL_VERIFYHOST => 0, \CURLOPT_TIMEOUT => 1]);
        curl_exec($c);
        if (200 === curl_getinfo($c, \CURLINFO_HTTP_CODE)) {
            return;
        }
        usleep(250_000);
    }
    exit("Traefik antwortet nicht\n");
};
$gespeichert = static fn (string $pfad): string => trim((string) shell_exec(
    "docker exec qa11u-db psql -U umami -d umami -tAc \"select count(*) from website_event where url_path='$pfad'\""
));
$aufruf = static fn (string $pfad): array => ['type' => 'event', 'payload' => ['website' => $website, 'hostname' => 'endlech.lu', 'url' => $pfad]];

$client = new CurlHttpClient(['cafile' => "$certs/ca.crt"]);
echo 'Stand: '.date('Y-m-d H:i')." · Weiterleitung mit cURL-Client, Vertrauen nur in die Wegwerf-CA\n";

foreach (['richtig' => 'gültig für localhost', 'falscher-name' => 'von der CA, aber für falsch.example', 'selbst' => 'selbst signiert für localhost'] as $fall => $beschreibung) {
    $zertifikat($fall);
    $weiterleitung = new UmamiForwarder($client, new ArrayAdapter(), $logger, new LockFactory(new InMemoryStore()), $upstream);
    $ergebnis = $weiterleitung->forward($aufruf("/de/qa-tls-$fall"), '158.64.1.1', $ua, null);
    sleep(1);
    printf("C · %-14s (%s): Antwort %d · gespeichert %s\n", $fall, $beschreibung, $ergebnis->status, $gespeichert("/de/qa-tls-$fall"));
}

$text = implode("\n", $protokoll);
printf("C · Protokoll: %d Einträge · Ausnahmeklassen: %s · Umami-Adresse (39443) darin: %d · Besucheradresse darin: %d\n",
    count($protokoll),
    implode(', ', array_unique(array_map(static fn ($m) => $m[1], preg_match_all('#"ausnahme":"([^"]+)"#', $text, $t, \PREG_SET_ORDER) ? $t : []))),
    substr_count($text, '39443'),
    substr_count($text, '158.64.1.1'),
);

// Gegenprobe: Mit abgeschalteter Prüfung im Client geht der falsche Name durch — die Prüfung oben ist also wirksam
// und nicht zufällig am Netz gescheitert.
$zertifikat('falscher-name');
$lax = new CurlHttpClient(['cafile' => "$certs/ca.crt", 'verify_peer' => false, 'verify_host' => false]);
$weiterleitung = new UmamiForwarder($lax, new ArrayAdapter(), $logger, new LockFactory(new InMemoryStore()), $upstream);
$ergebnis = $weiterleitung->forward($aufruf('/de/qa-tls-gegenprobe'), '158.64.1.1', $ua, null);
sleep(1);
printf("C · Gegenprobe falscher Name bei abgeschalteter Prüfung im Client: Antwort %d · gespeichert %s\n", $ergebnis->status, $gespeichert('/de/qa-tls-gegenprobe'));

$zertifikat('richtig');
