<?php

namespace App\Form;

use App\Entity\Cuisine;
use App\Entity\Restaurant;
use App\Enum\Language;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Valid;

class RestaurantType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.restaurant_name',
                'attr' => ['placeholder' => 'form.restaurant_name_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'restaurant.name_blank'),
                    new Length(max: 150, maxMessage: 'restaurant.name_max'),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'form.city',
                'attr' => ['placeholder' => 'form.city_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'restaurant.city_blank'),
                    new Length(max: 100, maxMessage: 'restaurant.city_max'),
                ],
            ])
            ->add('cuisines', EntityType::class, [
                'class' => Cuisine::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
                'label' => 'form.cuisines',
                'attr' => [
                    'data-controller' => 'tom-select',
                    'data-tom-select-url-value' => $this->urlGenerator->generate('api_cuisine_search'),
                    'data-tom-select-create-url-value' => $this->urlGenerator->generate('api_cuisine_create'),
                ],
            ])
            ->add('emoji', TextType::class, [
                'label' => 'form.emoji',
                'attr' => ['placeholder' => 'form.emoji_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'restaurant.emoji_blank'),
                    new Length(max: 10, maxMessage: 'restaurant.emoji_max'),
                ],
            ])
            ->add('rating', NumberType::class, [
                'label' => 'form.rating',
                'required' => false,
                'html5' => true,
                'attr' => ['placeholder' => 'form.rating_placeholder', 'step' => '0.1', 'min' => '0', 'max' => '10'],
                'constraints' => [
                    new Range(
                        min: 0,
                        max: 10,
                        notInRangeMessage: 'restaurant.rating_range',
                    ),
                ],
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'form.is_verified',
                'required' => false,
            ])
            ->add('isWheelchairAccessible', CheckboxType::class, [
                'label' => 'form.wheelchair_accessible',
                'required' => false,
            ])
            ->add('hasAccessibleToilet', CheckboxType::class, [
                'label' => 'form.accessible_toilet',
                'required' => false,
            ])
            ->add('allowsAssistanceDogs', CheckboxType::class, [
                'label' => 'form.assistance_dogs',
                'required' => false,
            ])
            ->add('hasBrightLighting', CheckboxType::class, [
                'label' => 'form.bright_lighting',
                'required' => false,
            ])
            ->add('hasChangingTable', CheckboxType::class, [
                'label' => 'form.changing_table',
                'required' => false,
            ])
            ->add('hasDisabledParking', CheckboxType::class, [
                'label' => 'form.disabled_parking',
                'required' => false,
            ])
            ->add('acceptsCash', CheckboxType::class, [
                'label' => 'form.accepts_cash',
                'required' => false,
            ])
            ->add('acceptsCard', CheckboxType::class, [
                'label' => 'form.accepts_card',
                'required' => false,
            ])
            ->add('acceptsPayconiq', CheckboxType::class, [
                'label' => 'form.accepts_payconiq',
                'required' => false,
            ])
            ->add('isVegan', CheckboxType::class, [
                'label' => 'form.vegan',
                'required' => false,
            ])
            ->add('isVegetarian', CheckboxType::class, [
                'label' => 'form.vegetarian',
                'required' => false,
            ])
            ->add('isHalal', CheckboxType::class, [
                'label' => 'form.halal',
                'required' => false,
            ])
            ->add('spokenLanguages', ChoiceType::class, [
                'label' => 'form.spoken_languages',
                'choices' => Language::cases(),
                'choice_value' => fn (?Language $l) => $l?->value,
                'choice_label' => fn (Language $l) => $l->flag().' '.$l->label(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'form.phone',
                'required' => false,
                'attr' => ['placeholder' => 'form.phone_placeholder'],
                'constraints' => [
                    new Length(max: 30, maxMessage: 'restaurant.phone_max'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email_field',
                'required' => false,
                'attr' => ['placeholder' => 'form.email_field_placeholder'],
                'constraints' => [
                    new Email(message: 'restaurant.email_invalid'),
                    new Length(max: 180, maxMessage: 'restaurant.email_max'),
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'form.website',
                'required' => false,
                'attr' => ['placeholder' => 'form.website_placeholder'],
                'constraints' => [
                    new Url(message: 'restaurant.url_invalid'),
                    new Length(max: 500, maxMessage: 'restaurant.url_max'),
                ],
            ])
            ->add('instagramUrl', UrlType::class, [
                'label' => 'form.instagram',
                'required' => false,
                'attr' => ['placeholder' => 'form.instagram_placeholder'],
                'constraints' => [
                    new Url(message: 'restaurant.url_invalid'),
                    new Length(max: 500, maxMessage: 'restaurant.url_max'),
                ],
            ])
            ->add('facebookUrl', UrlType::class, [
                'label' => 'form.facebook',
                'required' => false,
                'attr' => ['placeholder' => 'form.facebook_placeholder'],
                'constraints' => [
                    new Url(message: 'restaurant.url_invalid'),
                    new Length(max: 500, maxMessage: 'restaurant.url_max'),
                ],
            ])
            ->add('tiktokUrl', UrlType::class, [
                'label' => 'form.tiktok',
                'required' => false,
                'attr' => ['placeholder' => 'form.tiktok_placeholder'],
                'constraints' => [
                    new Url(message: 'restaurant.url_invalid'),
                    new Length(max: 500, maxMessage: 'restaurant.url_max'),
                ],
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'form.latitude',
                'required' => false,
                'html5' => true,
                'attr' => ['placeholder' => 'form.latitude_placeholder', 'step' => '0.00000001'],
                'constraints' => [
                    new Range(
                        min: -90,
                        max: 90,
                        notInRangeMessage: 'restaurant.latitude_range',
                    ),
                ],
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'form.longitude',
                'required' => false,
                'html5' => true,
                'attr' => ['placeholder' => 'form.longitude_placeholder', 'step' => '0.00000001'],
                'constraints' => [
                    new Range(
                        min: -180,
                        max: 180,
                        notInRangeMessage: 'restaurant.longitude_range',
                    ),
                ],
            ])
            ->add('nearbyStopsNote', TextType::class, [
                'label' => 'form.nearby_stops_note',
                'required' => false,
                'attr' => ['placeholder' => 'form.nearby_stops_note_placeholder'],
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'restaurant.nearby_stops_note_max'),
                ],
            ])
            ->add('orderingOptions', CollectionType::class, [
                'label' => 'form.ordering_options',
                'entry_type' => OrderingOptionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'required' => false,
                'constraints' => [
                    new Valid(),
                ],
            ])
            ->add('openingHours', CollectionType::class, [
                'label' => 'admin.form.opening_hours',
                'entry_type' => OpeningHourType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'required' => false,
                'constraints' => [
                    new Valid(),
                ],
            ])
            ->add('accessibilityNotes', CollectionType::class, [
                'label' => 'form.accessibility_notes',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'attr' => ['placeholder' => 'form.accessibility_notes_placeholder'],
                    'constraints' => [
                        new NotBlank(message: 'restaurant.note_blank'),
                        new Regex(
                            pattern: '/^(ok|warn):.+$/',
                            message: 'restaurant.note_format',
                        ),
                    ],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Restaurant::class,
        ]);
    }
}
