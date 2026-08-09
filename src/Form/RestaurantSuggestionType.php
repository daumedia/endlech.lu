<?php

namespace App\Form;

use App\Entity\RestaurantSuggestion;
use App\Enum\Language;
use App\Enum\TriState;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;

class RestaurantSuggestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.suggestion_name',
                'attr' => ['placeholder' => 'form.suggestion_name_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'suggestion.name_blank'),
                    new Length(max: 150, maxMessage: 'suggestion.name_max'),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'form.city',
                'attr' => ['placeholder' => 'form.city_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'suggestion.city_blank'),
                    new Length(max: 100, maxMessage: 'suggestion.city_max'),
                ],
            ])
            ->add('cuisine', TextType::class, [
                'label' => 'form.suggestion_cuisine',
                'attr' => ['placeholder' => 'form.suggestion_cuisine_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'suggestion.cuisine_blank'),
                    new Length(max: 80, maxMessage: 'suggestion.cuisine_max'),
                ],
            ])
            ->add('emoji', TextType::class, [
                'label' => 'form.suggestion_emoji',
                'attr' => ['placeholder' => 'form.emoji_placeholder'],
                'required' => false,
                'constraints' => [
                    new Length(max: 10, maxMessage: 'suggestion.emoji_max'),
                ],
            ]);

        // Barrierefreiheit (Step 2)
        $this->addTriState($builder, 'isWheelchairAccessible', 'form.wheelchair_accessible');
        $this->addTriState($builder, 'hasAccessibleToilet', 'form.accessible_toilet');
        $this->addTriState($builder, 'allowsAssistanceDogs', 'form.assistance_dogs');
        $this->addTriState($builder, 'hasBrightLighting', 'form.bright_lighting');
        $this->addTriState($builder, 'hasChangingTable', 'form.changing_table');
        $this->addTriState($builder, 'hasDisabledParking', 'form.disabled_parking');

        // Ernährung & Zahlung (Step 3)
        $this->addTriState($builder, 'isVegan', 'form.vegan');
        $this->addTriState($builder, 'isVegetarian', 'form.vegetarian');
        $this->addTriState($builder, 'isHalal', 'form.halal');
        $this->addTriState($builder, 'acceptsCash', 'form.accepts_cash');
        $this->addTriState($builder, 'acceptsCard', 'form.accepts_card');
        $this->addTriState($builder, 'acceptsPayconiq', 'form.accepts_payconiq');

        $builder
            ->add('spokenLanguages', ChoiceType::class, [
                'label' => 'form.spoken_languages',
                'choices' => Language::cases(),
                'choice_value' => fn (?Language $l) => $l?->value,
                'choice_label' => fn (Language $l) => $l->flag() . ' ' . $l->label(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'form.phone',
                'required' => false,
                'attr' => ['placeholder' => 'form.phone_placeholder'],
                'constraints' => [
                    new Length(max: 30, maxMessage: 'suggestion.phone_max'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email_field',
                'required' => false,
                'attr' => ['placeholder' => 'form.email_field_placeholder'],
                'constraints' => [
                    new Email(message: 'suggestion.email_invalid'),
                    new Length(max: 180, maxMessage: 'suggestion.email_max'),
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'form.website',
                'required' => false,
                'attr' => ['placeholder' => 'form.website_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
            ])
            ->add('instagramUrl', UrlType::class, [
                'label' => 'form.instagram',
                'required' => false,
                'attr' => ['placeholder' => 'form.instagram_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
            ])
            ->add('facebookUrl', UrlType::class, [
                'label' => 'form.facebook',
                'required' => false,
                'attr' => ['placeholder' => 'form.facebook_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
            ])
            ->add('tiktokUrl', UrlType::class, [
                'label' => 'form.tiktok',
                'required' => false,
                'attr' => ['placeholder' => 'form.tiktok_placeholder'],
                'constraints' => [
                    new Url(message: 'suggestion.url_invalid'),
                    new Length(max: 500, maxMessage: 'suggestion.url_max'),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'form.suggestion_notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.suggestion_notes_placeholder',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'suggestion.notes_max'),
                ],
            ]);
    }

    /**
     * Pflichtfrage mit drei Antworten (Ja / Nein / Weiß nicht) als Radio-Gruppe.
     *
     * 'error_bubbling' => false ist nötig, weil ein expanded ChoiceType ein
     * compound Feld ist und dort standardmäßig true gilt – die Fehler landeten
     * sonst am Root-Formular statt am Feld, und der Wizard könnte den fehler-
     * haften Step nicht mehr ermitteln.
     */
    private function addTriState(FormBuilderInterface $builder, string $name, string $label): void
    {
        $builder->add($name, ChoiceType::class, [
            'label' => $label,
            'choices' => TriState::cases(),
            'choice_value' => fn (?TriState $state) => $state?->value,
            'choice_label' => fn (TriState $state) => $state->transKey(),
            'expanded' => true,
            'multiple' => false,
            'placeholder' => false,
            'required' => true,
            'error_bubbling' => false,
            'constraints' => [
                new NotNull(message: 'suggestion.answer_required'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RestaurantSuggestion::class,
        ]);
    }
}
