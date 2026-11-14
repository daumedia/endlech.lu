<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SavedList;
use App\Saved\SavedText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Bereich „In Listen“ auf der Detailseite: ein Häkchen je eigener Liste und ein Feld für eine
 * neue (Feature 17, AK-21 bis AK-24). Ohne Datenklasse; die Daten sind
 * `['lists' => int[], 'newList' => string]`, vorbelegt vom Controller mit den Listen, in denen
 * das Lokal schon steht. Ohne eigene Liste bleibt die Auswahl leer und die Vorlage zeigt nur
 * das Feld „Neue Liste“ (AK-24).
 *
 * ⚠ **Die Auswahl sind Listennummern, die Beschriftung ist der Name — unübersetzt.** Ein
 * Listenname ist Text der Nutzerin; liefe er durch den Übersetzer, stünde bei einer Liste, die
 * zufällig wie ein Katalogschlüssel heißt, dessen Übersetzung. Der Name kommt über einen Rückruf,
 * nicht als Schlüssel des Auswahl-Arrays — Auswahl und Beschriftung bleiben getrennt.
 *
 * ⚠ **Fremde Nummern fängt der Controller vorher ab (404).** Kommt trotzdem eine unbekannte hier
 * an, ist sie ein gewöhnlicher Formularfehler am Feld, keine Ausnahme.
 *
 * ⚠ **`newList` prüft nur die Länge, keine Pflicht** — leer heißt „keine neue Liste“. Dublette
 * und 25er-Grenze (AK-19, AK-20) prüft der `SavedListService`; scheitert der Name, übernimmt der
 * Controller auch die Häkchen aus demselben Absenden nicht (AK-23). Kein `maxlength` (AK-18).
 */
final class RestaurantListsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<SavedList> $listen */
        $listen = $options['lists'];

        /** @var array<int, string> $namen Listennummer → Name */
        $namen = [];
        foreach ($listen as $liste) {
            // Eine ungespeicherte Liste hat keine Nummer, die ein Häkchen zurückmelden könnte.
            if (null !== $id = $liste->getId()) {
                $namen[$id] = $liste->getName();
            }
        }

        $builder
            ->add('lists', ChoiceType::class, [
                'label' => 'saved.panel.lists_heading',
                'choices' => array_keys($namen),
                'choice_label' => static fn (int $id): string => $namen[$id],
                'choice_translation_domain' => false,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                // Ausdrücklich, obwohl ChoiceType es in Symfony 8 schon vorgibt: Ein aufgeklappter
                // ChoiceType ist zusammengesetzt, und der Fehler gehört ans Feld, nicht an die
                // Wurzel (AK-50; vgl. Tri-State-Wizard in CLAUDE.md).
                'error_bubbling' => false,
            ])
            ->add('newList', TextType::class, [
                'label' => 'saved.panel.new_label',
                'help' => 'saved.panel.new_help',
                'required' => false,
                // ⚠ Pflicht, sonst kommt bei leerem Feld `null` statt '' beim Dienst an (BF-27).
                'empty_data' => '',
                'constraints' => [
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
        // Die eigenen Listen des angemeldeten Kontos — der Controller holt sie, das Formular
        // kennt kein Konto.
        $resolver->setRequired('lists');
        $resolver->setAllowedTypes('lists', SavedList::class.'[]');
    }
}
