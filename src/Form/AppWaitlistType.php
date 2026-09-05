<?php

namespace App\Form;

use App\Entity\AppWaitlistEntry;
use App\Enum\AppPlatform;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Vormerkung für die mobile App (Feature 08).
 *
 * Das schmalste Formular des Projekts: Adresse und Plattform, dazu die beiden
 * Häkchen. Ein Namensfeld brächte hier keinen Nutzen, den die Adresse nicht
 * schon hat — und jedes Pflichtfeld mehr kostet Eintragungen.
 */
class AppWaitlistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('platform', ChoiceType::class, [
                'label' => 'app_waitlist.form.platform',
                'choices' => AppPlatform::cases(),
                'choice_value' => fn (?AppPlatform $p) => $p?->value,
                'choice_label' => fn (AppPlatform $p) => $p->transKey(),
                'expanded' => true,
                'multiple' => false,
                // ⚠ Keine Vorauswahl: `placeholder: false` zusammen mit dem
                // Entity-Wert null lässt jedes Segment unmarkiert. Erst dadurch
                // liefert ein Submit ohne Auswahl verlässlich 422 statt still
                // die erste Plattform zu nehmen (AK-04).
                'placeholder' => false,
                'required' => true,
                // ⚠ Ein expanded ChoiceType ist compound, und dort ist
                // error_bubbling per Default true. Ohne diese Zeile landete die
                // Meldung am Root-Formular, und die Anzeige am Feld bliebe leer
                // (Muster aus RestaurantSuggestionType und OrganisationWaitlistType).
                'error_bubbling' => false,
                'constraints' => [
                    new NotNull(message: 'app_waitlist.platform_required'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'app_waitlist.form.email',
                // Ohne empty_data liefert ein leeres Feld null – der Setter
                // erwartet aber string und würde einen 500er statt der
                // NotBlank-Meldung erzeugen (Konvention „Die Prüfung gehört
                // dorthin, wo der Wert hereinkommt").
                'empty_data' => '',
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'app_waitlist.email_blank'),
                    // ⚠ **BF-119: `mode: strict`, nicht der HTML5-Default.**
                    // Der Default lässt Adressen durch, die
                    // `Symfony\Component\Mime\Address` anschließend nach
                    // RFC 2822 ablehnt — gemessen mit
                    // `../../etc/passwd@example.lu`: HTTP 500 beim Mailversand,
                    // und weil `register()` VOR dem Versand speichert, blieb
                    // eine Zeile stehen. Die Eingabeprüfung muss gegen dieselbe
                    // Norm prüfen wie der Empfänger, sonst fällt der Wert in
                    // die nächste Schicht (Konvention „Die Prüfung gehört
                    // dorthin, wo der Wert hereinkommt").
                    new Email(message: 'app_waitlist.email_invalid', mode: Email::VALIDATION_MODE_STRICT),
                    new Length(max: 180, maxMessage: 'app_waitlist.email_max'),
                ],
            ])
            // Einwilligung wird nicht auf die Entity gemappt – gespeichert wird
            // der Zeitpunkt (consentAt), nicht das Häkchen selbst. Art. 7 Abs. 1
            // DSGVO verlangt, die Einwilligung nachweisen zu können.
            ->add('consent', CheckboxType::class, [
                'label' => 'app_waitlist.form.consent',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue(message: 'app_waitlist.consent_required'),
                ],
            ])
            // Werbe-Einwilligung (Feature 04) – wie `consent` nicht gemappt.
            //
            // ⚠ Koppelungsverbot (Art. 7 Abs. 4 DSGVO): bewusst OHNE
            // IsTrue-Constraint und mit required: false. Die Mails zur App
            // selbst hängen NICHT hieran – sie sind der Zweck der Vormerkung.
            // Ein Zwang machte jede Einwilligung in dieser Liste unwirksam.
            //
            // ⚠ Keine Vorbelegung. Kein 'data' => true – ein vorangehaktes
            // Kästchen ist keine Einwilligung.
            ->add('marketingConsent', CheckboxType::class, [
                'label' => 'marketing.consent.label',
                'help' => 'marketing.consent.help',
                'mapped' => false,
                'required' => false,
            ])
            // Honeypot: bewusst KEIN type="hidden" – das füllen Bots
            // zuverlässig aus. Ein normales Textfeld mit plausiblem Namen wird
            // per CSS aus dem Blickfeld genommen und im Template zusätzlich mit
            // aria-hidden + tabindex="-1" aus Screenreader und Tab-Reihenfolge
            // gehalten.
            //
            // Bewusst OHNE Blank-Constraint: Ein Validierungsfehler würde dem
            // Bot verraten, welches Feld die Falle ist. Der Controller prüft das
            // Feld und liefert dieselbe Erfolgsantwort wie sonst – nur ohne zu
            // speichern und ohne Mail.
            ->add('website', TextType::class, [
                'label' => 'app_waitlist.form.honeypot',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppWaitlistEntry::class,
        ]);
    }
}
