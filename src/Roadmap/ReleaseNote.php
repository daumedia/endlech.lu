<?php

namespace App\Roadmap;

/**
 * Ein Release im öffentlichen Changelog (Feature 07).
 *
 * ⚠ **Auch die nicht gezeigten Releases stehen in der Liste.** Ohne sie könnte
 * `ChangelogCompletenessTest` nicht zwischen „bewusst still" und „vergessen"
 * unterscheiden (AK-26) — und der neue fünfte Punkt der Release-Checkliste wäre
 * eine Bitte statt einer Absicherung.
 */
final readonly class ReleaseNote
{
    public function __construct(
        public string $version,
        public \DateTimeImmutable $date,
        public ReleaseVisibility $visibility,
    ) {
    }

    public function isShown(): bool
    {
        return ReleaseVisibility::SHOWN === $this->visibility;
    }

    public function year(): string
    {
        return $this->date->format('Y');
    }

    /**
     * ⚠ **Punkte werden zu Unterstrichen.** Ein YAML-Schlüssel `2026.08.30.2`
     * würde von Symfony als vierfach verschachtelter Baum gelesen; die Zahl `2026`
     * wäre dabei ein numerischer Schlüssel. Der Unterstrich hält den Schlüssel
     * flach und lesbar. Das `v` davor, weil ein YAML-Schlüssel, der mit einer
     * Ziffer beginnt, je nach Parser als Zahl ankommt.
     */
    public function slug(): string
    {
        return 'v'.str_replace('.', '_', $this->version);
    }

    public function titleKey(): string
    {
        return 'release.'.$this->slug().'.title';
    }

    public function bodyKey(): string
    {
        return 'release.'.$this->slug().'.body';
    }
}
