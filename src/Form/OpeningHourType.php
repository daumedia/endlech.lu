<?php

namespace App\Form;

use App\Entity\OpeningHour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpeningHourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Echte outline statt focus:ring/outline-none: bleibt im Windows-Kontrastmodus
        // sichtbar (BF-76, AK-40). Kanon-Kette wie im übrigen Admin.
        $timeAttr = ['class' => 'bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-2 focus:outline-offset-2 focus:outline-purple-700 w-32'];

        $builder
            ->add('dayOfWeek', HiddenType::class)
            ->add('openTime', TimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => $timeAttr,
            ])
            ->add('closeTime', TimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => $timeAttr,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpeningHour::class,
        ]);
    }
}
