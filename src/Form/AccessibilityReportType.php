<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Meldeformular für eine Barriere (Feature 02).
 *
 * Bewusst OHNE data_class: Die Meldung wird nicht gespeichert (AK-50), es gibt
 * keine Entity. Der Controller liest `description` und `email` aus dem Array und
 * gibt sie an den AccessibilityReportMailer weiter.
 *
 * Verlangt wird NUR die Beschreibung (AK-58) — kein Name, kein Geburtsdatum,
 * keine Art der Behinderung. Die E-Mail ist freiwillig (AK-49).
 */
final class AccessibilityReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'accessibility_statement.report_description_label',
                // Ohne empty_data käme null; NotBlank meldet dann sauber statt
                // eines Typfehlers in der nächsten Schicht.
                'empty_data' => '',
                'attr' => ['rows' => 5],
                'constraints' => [
                    new NotBlank(message: 'accessibility.report.description_required'),
                    new Length(max: 5000),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'accessibility_statement.report_email_label',
                'required' => false,
                'empty_data' => '',
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new Email(message: 'accessibility.report.email_invalid'),
                    new Length(max: 180),
                ],
            ])
            // Honeypot — Muster aus PartnerWaitlistType: bewusst KEIN type="hidden"
            // (das füllen Bots), sondern ein normales Feld, das im Template per CSS
            // aus dem Blickfeld und per aria-hidden + tabindex="-1" aus Screenreader
            // und Tab-Reihenfolge genommen wird. OHNE Blank-Constraint, damit ein
            // Validierungsfehler die Falle nicht verrät; der Controller wertet das
            // Feld aus und antwortet bei einem Treffer mit derselben Erfolgsmeldung.
            // `label => false`: Das Feld ist versteckt und braucht keinen sichtbaren
            // Text (und damit keinen Katalogschlüssel).
            ->add('website', TextType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Kein data_class: Die Meldung wird nicht persistiert (AK-50); die Daten
        // stehen als Array zur Verfügung.
        $resolver->setDefaults([]);
    }
}
