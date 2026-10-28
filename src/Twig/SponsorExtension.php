<?php

namespace App\Twig;

use App\Sponsor\SponsorDirectory;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Stellt die Sponsoren für Footer, Übersicht, Unternehmensseite und
 * Faktenblatt bereit (Feature 15).
 *
 * ⚠ **Eine Erweiterung und nicht eine Controller-Variable** — dasselbe Muster
 * wie `ComparisonExtension`: Der Footer steht auf jeder Seite, und der erste
 * Controller, der die Liste vergäße, lieferte eine Seite mit halbem Footer.
 */
class SponsorExtension extends AbstractExtension
{
    public function __construct(
        private readonly SponsorDirectory $sponsors,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // Footer und Unternehmensseite: die ersten zwölf aktiven (AK-06, AK-31).
            new TwigFunction('sponsors_footer', $this->sponsors->footer(...)),
            // Übersicht: alle aktiven, auch über zwölf (AK-12), und die ehemaligen (AK-15).
            new TwigFunction('sponsors_active', $this->sponsors->active(...)),
            new TwigFunction('sponsors_former', $this->sponsors->former(...)),
            // Faktenblatt (AK-32).
            new TwigFunction('sponsors_active_count', fn (): int => \count($this->sponsors->active())),
            // „Alle Sponsoren“ im Footer, sobald es einen aktiven oder ehemaligen gibt (AK-05).
            new TwigFunction('sponsors_exist', $this->sponsors->hasAny(...)),
            new TwigFunction('sponsor_is_active', $this->sponsors->isActive(...)),
            // „Sponsor seit …“ und die Zeiträume auf der Sponsorenseite (AK-12, AK-16).
            new TwigFunction('sponsor_current_period', $this->sponsors->currentPeriod(...)),
            new TwigFunction('sponsor_periods', $this->sponsors->startedPeriods(...)),
        ];
    }
}
