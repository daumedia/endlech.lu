<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SavedList;
use App\Saved\SavedText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Name einer eigenen Liste — beim Anlegen auf „Gespeichert“ und beim Umbenennen (Feature 17,
 * AK-16 bis AK-18, AK-28). Ohne Datenklasse: Gespeichert wird über den `SavedListService`, der
 * auch Dublette und 25er-Grenze prüft (AK-19, AK-20); deren Meldung hängt der Controller ans Feld.
 *
 * ⚠ **Der Normalisierer ist `SavedText::name()` — dieselbe Funktion, mit der der Dienst
 * speichert.** Das Trimmen erledigt Symfonys `trim` ohnehin; NFC nicht. Ohne Normalisierer zählte
 * ein „é“ aus zwei Codepunkten doppelt, und das Formular wiese einen Namen ab, den der Dienst mit
 * 60 Zeichen gespeichert hätte.
 *
 * ⚠ **Kein `maxlength`** (anders als `BoardIdeaType`): Der Browser schnitte einen eingefügten
 * Namen still ab — es gäbe keine Meldung, und was fehlt, sähe niemand (AK-18).
 */
final class SavedListNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => $options['name_label'],
            'help' => 'saved.lists.new_help',
            'required' => true,
            // ⚠ `empty_data` ist Pflicht: Ohne die Zeile wird aus einem fehlenden Feld `null`,
            // der Dienst verlangt ein striktes `string`, und der Nutzer bekäme einen
            // Serverfehler statt der NotBlank-Meldung direkt daneben (BF-27).
            'empty_data' => '',
            'constraints' => [
                new NotBlank(message: 'saved.name_blank', normalizer: [SavedText::class, 'name']),
                new Length(
                    max: SavedList::MAX_NAME_LENGTH,
                    maxMessage: 'saved.name_too_long',
                    normalizer: [SavedText::class, 'name'],
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Dieselbe Klasse dient dem Umbenennen, dort mit `saved.list.rename_label` (AK-28).
        $resolver->setDefault('name_label', 'saved.lists.new_label');
        $resolver->setAllowedTypes('name_label', 'string');
    }
}
