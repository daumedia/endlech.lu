<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\RestaurantNote;
use App\Saved\SavedText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Bereich „Deine Notiz“ auf der Detailseite (Feature 17, AK-32 bis AK-36). Ohne Datenklasse;
 * gespeichert und geleert wird über den `RestaurantNoteService`.
 *
 * ⚠ **Leer ist kein Fehler, sondern das Löschen** (AK-36, EC-08) — deshalb keine Pflicht, nur
 * die Länge.
 *
 * ⚠ **Gezählt wird nach `SavedText::note()`**, derselben Funktion, mit der der Dienst speichert:
 * `\r\n` als ein Umbruch, Rand weg, NFC. Den Umbruch vereinheitlicht Symfonys TextareaType schon
 * selbst; NFC nicht.
 *
 * ⚠ **Kein `maxlength`:** Der Browser schnitte einen eingefügten Text still ab. So erscheint die
 * Meldung, und der Text bleibt im Feld, damit nichts verloren geht (AK-35).
 */
final class RestaurantNoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextareaType::class, [
            'label' => 'saved.panel.note_label',
            'label_translation_parameters' => ['%name%' => $options['restaurant_name']],
            'help' => 'saved.panel.note_help',
            'required' => false,
            // ⚠ Pflicht: Ein geleertes Feld muss als '' ankommen, nicht als `null` (BF-27).
            'empty_data' => '',
            'constraints' => [
                new Length(
                    max: RestaurantNote::MAX_LENGTH,
                    maxMessage: 'saved.note_too_long',
                    normalizer: [SavedText::class, 'note'],
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Name des Lokals für die Beschriftung „Notiz zu %name%“ — sonst stünde neben dem Feld
        // nicht, wozu die Notiz gehört.
        $resolver->setRequired('restaurant_name');
        $resolver->setAllowedTypes('restaurant_name', 'string');
    }
}
