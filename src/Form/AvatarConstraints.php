<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Die eine Regel für Profilbilder — im Profil (B04) und in der Einführung (Feature 12, AK-26).
 *
 * Steht an einer Stelle, weil die Spec „dieselbe Meldung wie im Profil" verlangt und zwei
 * Kopien beim ersten Anheben der Grenze auseinanderliefen.
 *
 * ⚠ `mimeTypes` prüft den **erkannten** Typ aus dem Dateiinhalt, nicht die Endung — eine in
 * `bild.jpg` umbenannte Textdatei fällt durch (AK-27).
 */
final class AvatarConstraints
{
    /**
     * @return list<File>
     */
    public static function file(): array
    {
        return [
            new File(
                maxSize: '2M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                maxSizeMessage: 'profile.avatar_max_size',
                mimeTypesMessage: 'profile.avatar_mime_type',
            ),
        ];
    }

    /**
     * Dieselbe Meldung, die die Einschränkung bei einem zu großen Bild erzeugt — für den Fall, dass
     * PHP den Rumpf wegen `post_max_size` verworfen hat und die Einschränkung gar nicht zum Zug kommt
     * (BF-159, Muster BF-58). Die Grenze kommt aus `file()`, nicht aus einer zweiten Zahl.
     */
    public static function tooLargeMessage(TranslatorInterface $translator): string
    {
        $grenze = self::file()[0]->maxSize ?? 0;

        return $translator->trans('profile.avatar_max_size', [
            '{{ limit }}' => (string) round($grenze / 1000000, 1),
            '{{ suffix }}' => 'MB',
        ], 'validators');
    }

    /**
     * Hat PHP den Rumpf verworfen, weil er `post_max_size` überschritt? Dann fehlen Datei, Felder und
     * CSRF-Token, nur `CONTENT_LENGTH` überlebt als Kopf.
     */
    public static function bodyWasDiscarded(Request $request): bool
    {
        return [] === $request->request->all()
            && [] === $request->files->all()
            && (int) $request->server->get('CONTENT_LENGTH') > 0;
    }
}
