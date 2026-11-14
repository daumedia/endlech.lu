<?php

declare(strict_types=1);

namespace App\Twig;

use App\Saved\PrivatePages;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `is_private_page()` für den Seitenkopf (Feature 17, AK-63): Zeigt die Seite einem angemeldeten
 * Konto private Angaben, legt Turbo von ihr keinen Schnappschuss an.
 *
 * Die Bedingung steht in {@see PrivatePages} — dieselbe, nach der `PrivatePageCacheSubscriber`
 * `no-store` setzt. Maßgeblich ist die **Hauptanfrage** (Muster `UsageExtension`): Eine
 * eingebettete Unteranfrage trägt eine andere Route als die Seite, in der sie steht.
 */
final class PrivatePageExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requests,
        private readonly PrivatePages $pages,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_private_page', fn (): bool => $this->pages->isPrivateView($this->requests->getMainRequest())),
        ];
    }
}
