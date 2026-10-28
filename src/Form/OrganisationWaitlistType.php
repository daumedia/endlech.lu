<?php

namespace App\Form;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\CollaborationInterest;
use App\Enum\OrganisationTimeframe;
use App\Enum\OrganisationType;
use App\Enum\SponsorshipTerm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Ein Formular für beide Organisationstypen (Gemeinde; Unternehmen oder Verein).
 *
 * Die typspezifischen Felder werden serverseitig in PRE_SUBMIT anhand des
 * übermittelten `type` hinzugefügt – dadurch funktioniert der Wechsel auch ohne
 * JavaScript, und Felder eines fremden Typs können gar nicht erst gemappt
 * werden. Der Stimulus-Controller blendet dieselben Blöcke im Browser nur
 * zusätzlich ein und aus.
 */
class OrganisationWaitlistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'organisation.form.type',
                'choices' => OrganisationType::cases(),
                'choice_value' => fn (?OrganisationType $t) => $t?->value,
                'choice_label' => fn (OrganisationType $t) => $t->transKey(),
                'expanded' => true,
                'multiple' => false,
                'placeholder' => false,
                'required' => true,
                // Compound-Feld: Ohne das landen die Fehler am Root-Formular
                // und die Feldanzeige bliebe leer (Muster aus RestaurantSuggestionType).
                'error_bubbling' => false,
            ])
            ->add('organisationName', TextType::class, [
                'label' => 'organisation.form.organisation_name',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'organization'],
                'constraints' => [
                    new NotBlank(message: 'organisation_waitlist.organisation_name_blank'),
                    new Length(max: 180, maxMessage: 'organisation_waitlist.organisation_name_max'),
                ],
            ])
            ->add('contactName', TextType::class, [
                'label' => 'organisation.form.contact_name',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'name'],
                'constraints' => [
                    new NotBlank(message: 'organisation_waitlist.contact_name_blank'),
                    new Length(max: 120, maxMessage: 'organisation_waitlist.contact_name_max'),
                ],
            ])
            ->add('contactRole', TextType::class, [
                'label' => 'organisation.form.contact_role',
                'required' => false,
                'attr' => ['autocomplete' => 'organization-title'],
                'constraints' => [
                    new Length(max: 120, maxMessage: 'organisation_waitlist.contact_role_max'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'organisation.form.email',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'organisation_waitlist.email_blank'),
                    // ⚠ BF-119: `VALIDATION_MODE_STRICT` statt des HTML5-Defaults.
                    // Der Default lässt Adressen durch, die `Mime\Address` nach
                    // RFC 2822 ablehnt (`../../etc/passwd@example.lu`) — der Versand
                    // wirft dann eine `RfcComplianceException`, und weil vor dem
                    // Versand gespeichert wird, bleibt die Zeile stehen: 500er plus
                    // Datensatz. Gemessen an Feature 08, wo STRICT schon steht:
                    // acht realistische Adressen unverändert akzeptiert, zusätzlich
                    // abgelehnt werden nur Local-Parts über 64 Zeichen (die RFC 5321
                    // ohnehin verbietet). Umgekehrt akzeptiert STRICT
                    // `jean-luc@télécom.lu`, das der Default still abwies.
                    new Email(message: 'organisation_waitlist.email_invalid', mode: Email::VALIDATION_MODE_STRICT),
                    new Length(max: 180, maxMessage: 'organisation_waitlist.email_max'),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'organisation.form.phone',
                'required' => false,
                'attr' => ['autocomplete' => 'tel'],
                'constraints' => [
                    new Length(max: 40, maxMessage: 'organisation_waitlist.phone_max'),
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'organisation.form.website',
                'required' => false,
                'default_protocol' => 'https',
                'constraints' => [
                    new Url(message: 'organisation_waitlist.website_invalid', requireTld: true),
                    new Length(max: 255, maxMessage: 'organisation_waitlist.website_max'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'organisation.form.message',
                'required' => false,
                'attr' => ['rows' => 4],
                'constraints' => [
                    new Length(max: 2000, maxMessage: 'organisation_waitlist.message_max'),
                ],
            ])
            ->add('consent', CheckboxType::class, [
                'label' => 'organisation.form.consent',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue(message: 'organisation_waitlist.consent_required'),
                ],
            ])
            // Werbe-Einwilligung (Feature 04) – wie `consent` nicht gemappt, weil
            // die Entity den Zeitpunkt speichert (marketingConsentAt) und nicht
            // das Häkchen selbst.
            //
            // ⚠ Das Feld steht hier im typunabhängigen Teil, NICHT in
            // addTypeSpecificFields(). Die Einwilligung gilt für Gemeinden,
            // Unternehmen und Vereine gleichermaßen, und nur an dieser Stelle ist
            // sie in beiden Feldaufbauten vorhanden: beim Rendern (PRE_SET_DATA)
            // und beim Absenden (PRE_SUBMIT). Läge sie in einem der Typblöcke,
            // wäre sie für den anderen Typ ein unerlaubtes Zusatzfeld und
            // jeder Submit mit gesetztem Häkchen endete in einem 422 – genau der
            // Mechanismus, den testCrossTypeFieldsAreRejected nachweist.
            //
            // ⚠ AK-03, Koppelungsverbot (Art. 7 Abs. 4 DSGVO): bewusst OHNE
            // IsTrue-Constraint und mit required: false. Die Einwilligung darf
            // keine Bedingung für die Anmeldung sein – bleibt das Feld leer,
            // läuft der Vorgang unverändert durch. Ein Zwang machte jede
            // Einwilligung in dieser Liste unwirksam.
            //
            // ⚠ AK-02: keine Vorbelegung. Kein 'data' => true – ein
            // vorangehaktes Kästchen ist keine Einwilligung.
            ->add('marketingConsent', CheckboxType::class, [
                'label' => 'marketing.consent.label',
                'help' => 'marketing.consent.help',
                'mapped' => false,
                'required' => false,
            ])
            // Honeypot ohne Constraint – der Controller prüft ihn und antwortet
            // wie bei einem echten Erfolg, damit die Falle nicht auffliegt.
            ->add('companyWebsite', TextType::class, [
                'label' => 'organisation.form.honeypot',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
            ]);

        // Beim Rendern (GET) werden ALLE typspezifischen Blöcke aufgebaut.
        // Das ist die Voraussetzung dafür, dass die Seite ohne JavaScript
        // benutzbar ist: Wer kein JS hat, sieht beide Blöcke (jeweils
        // beschriftet) und füllt den passenden aus. Mit JavaScript blendet der
        // Stimulus-Controller die unpassenden aus.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            foreach (OrganisationType::cases() as $type) {
                $this->addTypeSpecificFields($event->getForm(), $type);
            }
        });

        // Beim Absenden zählt allein der übermittelte Typ. Felder eines
        // fremden Typs existieren dadurch gar nicht und können nicht gesetzt
        // werden – die Validierungsgruppen sichern denselben Fall zusätzlich ab.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $type = \is_array($data) ? OrganisationType::tryFrom((string) ($data['type'] ?? '')) : null;

            // Feature 15 · AK-37/AK-38: Radios nehmen in Symfony auch eine Liste an und
            // behalten still einen der Werte. Zwei Laufzeiten auf einmal sind aber keine
            // Antwort, sondern ein manipulierter Aufruf — er soll scheitern (422) wie jedes
            // untergeschobene Feld, statt still gespeichert zu werden. Der Ersatzwert ist
            // keine gültige Wahl und lässt die Auswahlprüfung des Feldes fehlschlagen.
            if (\is_array($data) && \is_array($data['sponsorshipTerm'] ?? null)) {
                $data['sponsorshipTerm'] = '__mehrere__';
                $event->setData($data);
            }

            $this->addTypeSpecificFields($event->getForm(), $type);
        });
    }

    private function addTypeSpecificFields(FormInterface $form, ?OrganisationType $type): void
    {
        if (OrganisationType::COMMUNE === $type) {
            $form
                ->add('communeName', TextType::class, [
                    'label' => 'organisation.form.commune_name',
                    'required' => false,
                    'constraints' => [
                        new Length(max: 120, maxMessage: 'organisation_waitlist.commune_name_max'),
                    ],
                ])
                ->add('estimatedVenues', IntegerType::class, [
                    'label' => 'organisation.form.estimated_venues',
                    'required' => false,
                    'attr' => ['min' => 1, 'max' => 5000, 'inputmode' => 'numeric'],
                ])
                ->add('timeframe', ChoiceType::class, [
                    'label' => 'organisation.form.timeframe',
                    'required' => false,
                    'placeholder' => 'organisation.form.timeframe_placeholder',
                    'choices' => OrganisationTimeframe::cases(),
                    'choice_value' => fn (?OrganisationTimeframe $t) => $t?->value,
                    'choice_label' => fn (OrganisationTimeframe $t) => $t->transKey(),
                ]);

            return;
        }

        if (OrganisationType::COMPANY === $type) {
            // Unternehmen und Vereine teilen sich einen Typ und damit beide
            // Fragen: Sponsoring und Zusammenarbeit ohne Geld. Bis 2026-10-14
            // trug ein eigener Vereinstyp die zweite Liste (siehe OrganisationType).
            //
            // Seit Feature 15 (2026-10-28) gibt es EIN Sponsoring-Paket: Gefragt
            // wird nur noch die Laufzeit, als zwei Radios ohne dritte Antwort
            // (AK-37, Entscheidung #8). Leer bleiben darf die Frage — dann geht es
            // nur um Zusammenarbeit ohne Geld. Die Entity legt die Laufzeit im
            // bisherigen JSON-Feld ab (`setSponsorshipTerm()`); die sechs Häkchen
            // der früheren Formate gibt es nicht mehr.
            //
            // Die Choices der Zusammenarbeit bleiben reine Strings: Die Entity
            // speichert ein JSON-Array aus Strings, so passen Model- und
            // Choice-Werte ohne Transformer zusammen.
            $form
                ->add('sponsorshipTerm', ChoiceType::class, [
                    'label' => 'organisation.form.sponsorship_term',
                    'required' => false,
                    'multiple' => false,
                    'expanded' => true,
                    'placeholder' => false,
                    'choices' => SponsorshipTerm::cases(),
                    'choice_value' => static fn (?SponsorshipTerm $t): ?string => $t?->value,
                    'choice_label' => static fn (SponsorshipTerm $t): string => $t->transKey(),
                    'error_bubbling' => false,
                ])
                ->add('collaborationInterests', ChoiceType::class, [
                    'label' => 'organisation.form.collaboration_interests',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => self::enumChoices(CollaborationInterest::cases()),
                    'error_bubbling' => false,
                ]);
        }
    }

    /**
     * Baut aus Enum-Cases eine Choice-Liste [Übersetzungsschlüssel => Wert].
     *
     * @param array<CollaborationInterest> $cases
     *
     * @return array<string, string>
     */
    private static function enumChoices(array $cases): array
    {
        $choices = [];

        foreach ($cases as $case) {
            $choices[$case->transKey()] = $case->value;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrganisationWaitlistEntry::class,
            // Validierungsgruppe folgt dem gewählten Typ: So gelten für eine
            // Gemeinde andere Regeln als für ein Unternehmen, und die jeweils
            // fremden Felder müssen leer sein (siehe Constraints auf der Entity).
            'validation_groups' => static function (FormInterface $form): array {
                $data = $form->getData();
                $type = $data instanceof OrganisationWaitlistEntry ? $data->getType() : null;

                return $type ? ['Default', $type->value] : ['Default'];
            },
        ]);
    }
}
