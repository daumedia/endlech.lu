<?php
// QA Feature 11 · Trennt die Sperre der Weiterleitung gleichzeitige Anfragen in FrankenPHP? Nachbau von
// UmamiForwarder::platzBelegen() mit derselben Store-Art (FlockStore über LockFactory) und denselben Zahlen.
require '/projekt/vendor/autoload.php';
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
$start = microtime(true);
$platz = (new LockFactory(new FlockStore()))->createLock('umami-weiterleitung', 4);
$bekommen = false;
for ($gewartet = 0; ; $gewartet += 10) {
    if ($platz->acquire()) { $bekommen = true; break; }
    if ($gewartet >= 100) { break; }
    usleep(10_000);
}
if ($bekommen) { usleep(2_000_000); $platz->release(); }   // der „hängende" Aufruf
header('Content-Type: text/plain');
printf("%s pid=%d %.2fs\n", $bekommen ? 'platz' : 'belegt', getmypid(), microtime(true) - $start);
