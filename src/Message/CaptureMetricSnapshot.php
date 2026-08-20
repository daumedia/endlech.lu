<?php

namespace App\Message;

/**
 * Auftrag, die Monatswerte der Open-Startup-Seite festzuhalten.
 *
 * Ohne Nutzlast: Welcher Monat gemeint ist, ergibt sich aus dem
 * Ausführungszeitpunkt (MetricSnapshotService::defaultMonth()). Ein
 * mitgeschicktes Datum wäre bei einem verspätet nachgeholten Lauf falsch –
 * der Scheduler stellt Nachrichten zu, deren Zeitpunkt verstrichen ist.
 */
final class CaptureMetricSnapshot
{
}
