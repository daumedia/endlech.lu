<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Profilbild im dritten Bildschirm der Einführung (Feature 12, AK-25 bis AK-27).
 *
 * Ohne Datenklasse: Das Formular trägt nur die Datei, das Konto setzt der Controller — es gibt
 * kein Feld, über das eine fremde Kennung hineinkäme (AK-57).
 */
final class OnboardingAvatarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('avatar', FileType::class, [
            'label' => 'profile.form.avatar',
            'help' => 'profile.form.avatar_hint',
            // Ein Absenden ohne Datei ändert nichts — und ist kein Fehler (AK-15).
            'required' => false,
            'constraints' => AvatarConstraints::file(),
            'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // AK-57: Ein mitgeschicktes Feld wie `user=42` wird übergangen, nicht ausgewertet — das
        // Formular kennt kein Konto, der Controller nimmt immer das angemeldete.
        $resolver->setDefaults(['allow_extra_fields' => true]);
    }
}
