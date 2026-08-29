<?php

namespace App\Twig;

use App\Comparison\ComparisonGroup;
use App\Comparison\Competitor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Stellt die Liste der Vergleiche für die Fußzeile bereit (Feature 03).
 *
 * ⚠ **Eine Erweiterung und nicht eine Controller-Variable**, weil die Fußzeile
 * auf *jeder* Seite gerendert wird. Über den Controller ginge es nur, wenn jeder
 * der rund zwanzig Controller die Liste mitgäbe — und der erste, der es vergisst,
 * liefert eine Seite mit halber Fußzeile aus.
 *
 * ⚠ **Kein Twig-Global über `twig.yaml`** (wie bei `app_version`): Dort lassen
 * sich nur Werte hinterlegen, kein Aufruf von `Competitor::cases()`.
 */
class ComparisonExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('comparison_competitors', [$this, 'getCompetitors']),
            new TwigFunction('comparison_groups', [$this, 'getGroups']),
        ];
    }

    /**
     * @return list<Competitor>
     */
    public function getCompetitors(): array
    {
        return Competitor::cases();
    }

    /**
     * Die Gruppen der Merkmalstabelle in der Reihenfolge der Darstellung.
     *
     * Die Reihenfolge steht im Enum und ist eine Aussage: Erst was erfasst wird,
     * dann woher es kommt, dann wie viel davon da ist. Die Abdeckung steht an
     * dritter Stelle und nicht am Ende — sie ist die Gruppe, in der Endlech.lu
     * verliert.
     *
     * @return list<ComparisonGroup>
     */
    public function getGroups(): array
    {
        return ComparisonGroup::cases();
    }
}
