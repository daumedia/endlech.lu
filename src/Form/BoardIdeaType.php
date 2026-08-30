<?php

namespace App\Form;

use App\Entity\BoardIdea;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Das Einreichformular des Community-Boards (Feature 06).
 *
 * ⚠ **Kein Dateifeld.** Anhänge sind ausdrücklich nicht im Umfang (AK-63); ein
 * öffentlich ausgeliefertes Verzeichnis mit ungeprüften Fremddateien wäre eine
 * eigene Risikofläche. `allow_extra_fields` bleibt auf dem Standardwert `false`,
 * damit ein untergeschobenes Feld mit 422 abgewiesen wird statt still zu wirken.
 */
class BoardIdeaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'board.form_title',
                'help' => 'board.form_title_help',
                // ⚠ `empty_data` ist Pflicht: `BoardIdea::setTitle()` verlangt
                // ein striktes `string`. Ohne die Zeile übergibt Symfony `null`,
                // der Setter wirft, und der Nutzer bekommt einen Serverfehler
                // statt der NotBlank-Meldung, die direkt daneben steht (BF-27).
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(message: 'board.title_required'),
                    new Length(max: BoardIdea::TITLE_MAX, maxMessage: 'board.title_too_long'),
                ],
                'attr' => ['maxlength' => BoardIdea::TITLE_MAX],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'board.form_description',
                'help' => 'board.form_description_help',
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(message: 'board.description_required'),
                    new Length(max: BoardIdea::DESCRIPTION_MAX, maxMessage: 'board.description_too_long'),
                ],
                'attr' => ['maxlength' => BoardIdea::DESCRIPTION_MAX, 'rows' => 8],
            ])
            // Fallenfeld. Bewusst KEIN `type="hidden"` – das füllen Bots
            // zuverlässig; und bewusst ohne `Blank`-Constraint, weil ein
            // Validierungsfehler dem Bot verriete, welches Feld die Falle ist.
            // Der Controller prüft es und liefert bei einem Treffer dieselbe
            // Erfolgsantwort wie sonst – nur ohne zu speichern (AK-17).
            ->add('website', TextType::class, [
                'label' => 'board.form_honeypot',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BoardIdea::class,
        ]);
    }
}
