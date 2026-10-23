<?php

declare(strict_types=1);

/*
 * QA Feature 12, dritter Durchlauf (2026-10-23) — Treiber gegen den LAUFENDEN Server.
 *
 * Jedes `Geraet` ist ein eigener Browser ohne JavaScript (eigener Cookie-Speicher), wie `curl -c/-b`.
 * Weiterleitungen werden nicht verfolgt, damit Status und Ziel sichtbar bleiben. Den Zustand liest
 * `sql()` direkt aus der Wegwerf-Datenbank (Port 3309). Aufruf: `php qa/12/nachpruefung2/<skript>.php`.
 */

require_once __DIR__.'/../../../vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

const QA_DB = 'mysql:host=127.0.0.1;port=3309;dbname=endlech;charset=utf8mb4';

function pdo(): PDO
{
    static $pdo = null;

    return $pdo ??= new PDO(QA_DB, 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

/** @return list<array<string, mixed>> */
function sql(string $query, array $params = []): array
{
    $st = pdo()->prepare($query);
    $st->execute($params);

    return $st->columnCount() > 0 ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
}

function zeile(string $query, array $params = []): ?array
{
    return sql($query, $params)[0] ?? null;
}

function aus(string $text): void
{
    echo $text, "\n";
}

final class Geraet
{
    public HttpBrowser $b;
    public ?Crawler $c = null;

    public function __construct(public string $basis = 'http://127.0.0.1:8099', array $server = [])
    {
        $this->b = new HttpBrowser(HttpClient::create(['timeout' => 60, 'max_redirects' => 0]));
        $this->b->followRedirects(false);
        foreach ($server as $k => $v) {
            $this->b->setServerParameter($k, $v);
        }
    }

    public function get(string $pfad, array $server = []): self
    {
        $this->c = $this->b->request('GET', $this->basis.$pfad, [], [], $server);

        return $this;
    }

    public function head(string $pfad): self
    {
        $this->c = $this->b->request('HEAD', $this->basis.$pfad);

        return $this;
    }

    public function post(string $pfad, array $felder = [], array $dateien = [], array $server = []): self
    {
        $this->c = $this->b->request('POST', $this->basis.$pfad, $felder, $dateien, $server);

        return $this;
    }

    public function status(): int
    {
        return $this->b->getInternalResponse()->getStatusCode();
    }

    public function ziel(): string
    {
        $ort = (string) $this->b->getInternalResponse()->getHeader('Location');

        return preg_replace('#^https?://[^/]+#', '', $ort) ?? $ort;
    }

    public function html(): string
    {
        return $this->b->getInternalResponse()->getContent();
    }

    /** Sichtbarer Text des Hauptbereichs, Leerraum zusammengezogen. */
    public function text(string $selektor = 'main'): string
    {
        $knoten = $this->c?->filter($selektor);

        return null !== $knoten && $knoten->count() > 0 ? trim((string) preg_replace('/\s+/u', ' ', $knoten->text())) : '';
    }

    public function folgen(int $max = 5): self
    {
        for ($i = 0; $i < $max && \in_array($this->status(), [301, 302, 303, 307], true); ++$i) {
            $this->c = $this->b->followRedirect();
        }

        return $this;
    }

    /** Formular der aktuellen Seite (Selektor auf das <form>) mit Werten absenden. */
    public function absenden(string $formSelektor, array $werte = [], array $dateien = [], array $server = []): self
    {
        $form = $this->c->filter($formSelektor)->form();
        foreach ($werte as $k => $v) {
            if (true === $v) {
                $form[$k]->tick();
            } elseif (false === $v) {
                $form[$k]->untick();
            } else {
                $form[$k] = $v;
            }
        }
        foreach ($dateien as $k => $pfad) {
            $form[$k]->upload($pfad);
        }
        $this->c = $this->b->submit($form, [], $server);

        return $this;
    }

    public function anmelden(string $email, string $passwort, bool $bleiben = false, string $locale = 'de'): self
    {
        $this->get('/'.$locale.'/login');
        $werte = ['_username' => $email, '_password' => $passwort];
        if ($bleiben) {
            $werte['_remember_me'] = true;
        }

        $form = $this->c->filter('form')->reduce(static fn (Crawler $f): bool => $f->filter('input[name="_username"]')->count() > 0)->first()->form();
        foreach ($werte as $k => $v) {
            true === $v ? $form[$k]->tick() : $form[$k] = $v;
        }
        $this->c = $this->b->submit($form);

        return $this;
    }

    public function registrieren(string $name, string $email, string $passwort = 'Sicher-Passwort-2026!', bool $werbung = false, string $locale = 'de'): self
    {
        $this->get('/'.$locale.'/register');
        $form = $this->c->filter('form[name="registration"], form[name="registration_form"], form')->reduce(
            static fn (Crawler $f): bool => str_contains((string) $f->attr('action'), 'register') || '' === (string) $f->attr('action'),
        );
        $f = $form->first()->form();
        foreach ($f->all() as $name_ => $feld) {
            if (str_ends_with($name_, '[name]')) {
                $f[$name_] = $name;
            } elseif (str_ends_with($name_, '[email]')) {
                $f[$name_] = $email;
            } elseif (str_contains($name_, '[plainPassword]')) {
                $f[$name_] = $passwort;
            } elseif (str_ends_with($name_, '[agreeTerms]') || str_ends_with($name_, '[privacy]') || str_ends_with($name_, '[acceptPrivacy]')) {
                $feld->tick();
            } elseif (str_ends_with($name_, '[marketingConsent]') && $werbung) {
                $feld->tick();
            }
        }
        $this->c = $this->b->submit($f);

        return $this;
    }
}

/** Bestätigungslink eines Kontos aus der Datenbank (Mails gehen an null://null). */
function bestaetigungslink(string $email, string $locale = 'de'): string
{
    $t = zeile('SELECT verification_token FROM `user` WHERE email = ?', [$email])['verification_token'] ?? '';

    return '/'.$locale.'/verify/'.$t;
}

function konto(string $email): ?array
{
    return zeile('SELECT id, name, email, is_verified, onboarding_due, onboarding_shown_at, marketing_consent_at, avatar_filename, pending_email FROM `user` WHERE email = ?', [$email]);
}

function kontakt(string $email): ?array
{
    return zeile('SELECT id, email, origin, locale, sync_state, consent_at, revoked_at, synced_at, attempts FROM marketing_contact WHERE email = ?', [$email]);
}

function zeige(?array $z): string
{
    return null === $z ? 'NULL' : json_encode($z, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Registrierung über den Server mit vertrautem Proxy (:8097), je Aufruf eine andere Absenderadresse —
 * der Deckel `registration` (5/Std je IP) gilt im Entwicklungsmodus mit dem Produktionswert.
 */
function registriere(string $name, string $email, bool $werbung = false, string $locale = 'de'): Geraet
{
    static $n = 0;
    ++$n;
    $g = new Geraet('http://127.0.0.1:8097', ['HTTP_X_FORWARDED_FOR' => '10.33.'.random_int(0, 250).'.'.(($n % 250) + 1)]);
    $g->registrieren($name, $email, 'Sicher-Passwort-2026!', $werbung, $locale);
    if (302 !== $g->status() || null === konto($email)) {
        throw new RuntimeException("Registrierung $email gescheitert: {$g->status()}");
    }

    return $g;
}
