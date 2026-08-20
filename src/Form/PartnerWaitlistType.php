<?php

namespace App\Form;

use App\Entity\PartnerWaitlistEntry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PartnerWaitlistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('restaurantName', TextType::class, [
                'label' => 'partner.form.restaurant_name',
                // Ohne empty_data liefert ein leeres Feld null – der Setter
                // erwartet aber string und würde einen 500er statt einer
                // Validierungsmeldung erzeugen.
                'empty_data' => '',
                'attr' => ['autocomplete' => 'organization'],
                'constraints' => [
                    new NotBlank(message: 'partner_waitlist.restaurant_name_blank'),
                    new Length(max: 180, maxMessage: 'partner_waitlist.restaurant_name_max'),
                ],
            ])
            ->add('contactName', TextType::class, [
                'label' => 'partner.form.contact_name',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'name'],
                'constraints' => [
                    new NotBlank(message: 'partner_waitlist.contact_name_blank'),
                    new Length(max: 120, maxMessage: 'partner_waitlist.contact_name_max'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'partner.form.email',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'partner_waitlist.email_blank'),
                    new Email(message: 'partner_waitlist.email_invalid'),
                    new Length(max: 180, maxMessage: 'partner_waitlist.email_max'),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'partner.form.phone',
                'required' => false,
                'attr' => ['autocomplete' => 'tel'],
                'constraints' => [
                    new Length(max: 40, maxMessage: 'partner_waitlist.phone_max'),
                ],
            ])
            ->add('locality', TextType::class, [
                'label' => 'partner.form.locality',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'address-level2'],
                'constraints' => [
                    new NotBlank(message: 'partner_waitlist.locality_blank'),
                    new Length(max: 120, maxMessage: 'partner_waitlist.locality_max'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'partner.form.message',
                'required' => false,
                'attr' => ['rows' => 4],
                'constraints' => [
                    new Length(max: 2000, maxMessage: 'partner_waitlist.message_max'),
                ],
            ])
            // Einwilligung wird nicht auf die Entity gemappt – gespeichert wird
            // der Zeitpunkt (consentAt), nicht das Häkchen selbst.
            ->add('consent', CheckboxType::class, [
                'label' => 'partner.form.consent',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue(message: 'partner_waitlist.consent_required'),
                ],
            ])
            // Honeypot: bewusst KEIN type="hidden" – das füllen Bots zuverlässig
            // aus. Ein normales Textfeld mit plausiblem Namen wird per CSS aus
            // dem Blickfeld genommen und im Template zusätzlich mit
            // aria-hidden + tabindex="-1" aus Screenreader und Tab-Reihenfolge
            // gehalten. Menschen sehen es nie, Bots füllen es aus.
            //
            // Bewusst OHNE Blank-Constraint: Ein Validierungsfehler würde dem
            // Bot verraten, welches Feld die Falle ist. Der Controller prüft das
            // Feld und liefert stattdessen dieselbe Erfolgsantwort wie sonst –
            // nur ohne zu speichern und ohne Mail.
            ->add('website', TextType::class, [
                'label' => 'partner.form.honeypot',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PartnerWaitlistEntry::class,
        ]);
    }
}
