<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Das Newsletter-Häkchen im letzten Bildschirm der Einführung (Feature 12, AK-30 bis AK-33).
 *
 * Nicht vorangehakt und nicht Pflicht: Die Einwilligung ist für nichts eine Bedingung
 * (04/AK-02, 04/AK-03). Ohne Datenklasse — der Controller entscheidet über den gemeinsamen
 * Dienst, was mit dem Konto geschieht.
 */
final class OnboardingNewsletterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('consent', CheckboxType::class, [
            'label' => 'onboarding.newsletter.label',
            'help' => 'onboarding.newsletter.terms',
            'required' => false,
            'data' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // AK-57: Ein mitgeschicktes Feld wie `user=42` wird übergangen, nicht ausgewertet — das
        // Formular kennt kein Konto, der Controller nimmt immer das angemeldete.
        $resolver->setDefaults(['allow_extra_fields' => true]);
    }
}
