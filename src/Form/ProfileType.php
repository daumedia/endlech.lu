<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.name',
                // ⚠ BF-182: `empty_data` ist Pflicht — `User::setName()` verlangt ein striktes `string`.
                // `TextType` kürzt „   " auf „" und gibt ohne die Zeile `null` weiter; der Setter warf,
                // bevor `NotBlank` greifen konnte: HTTP 500 statt „Bitte gib deinen Namen ein." Ein
                // Browser schickt Leerzeichen ab (`required` gilt damit als erfüllt). Konvention „Die
                // Prüfung gehört dorthin, wo der Wert hereinkommt", Muster BF-27/BF-51.
                'empty_data' => '',
                'attr' => ['placeholder' => 'form.name_placeholder', 'autocomplete' => 'name'],
                'constraints' => [
                    new NotBlank(message: 'user.name_blank'),
                    new Length(min: 2, max: 100, minMessage: 'user.name_min'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                // ⚠ BF-182: wie beim Namen — `User::setEmail(string)`. Der Browser hält das leere Feld
                // ab, ein Skript nicht.
                'empty_data' => '',
                'attr' => ['placeholder' => 'form.email_placeholder', 'autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'user.email_blank'),
                    // ⚠ BF-184 (Muster BF-119): `VALIDATION_MODE_STRICT` statt des HTML5-Defaults.
                    // Der Default ließ `../../etc/passwd@example.lu` durch; `Mime\Address` lehnte sie
                    // erst beim Versand ab — nach dem `flush()`, die Vormerkung blieb stehen, ein
                    // Deckelplatz war verbraucht, der Nutzer sah HTTP 500. Das Profil war der fünfte
                    // Adresseingang, den BF-119 („alle vier Wege prüfen strikt") nicht erreicht hatte.
                    new Email(message: 'user.email_invalid', mode: Email::VALIDATION_MODE_STRICT),
                    // ⚠ BF-184: Die Spalten `email` und `pending_email` fassen 180 Zeichen. Eine
                    // RFC-konforme Adresse mit langer Domain besteht die strikte Prüfung und lief
                    // in `SQLSTATE[22001]`.
                    new Length(max: 180, maxMessage: 'user.email_max'),
                ],
            ])
            ->add('avatar', FileType::class, [
                'label' => 'profile.form.avatar',
                'mapped' => false,
                'required' => false,
                // Seit Feature 12 an einer Stelle — die Einführung prüft mit derselben Regel.
                'constraints' => AvatarConstraints::file(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // Hier IST die Auskunft unbedenklich: Der Nutzer bearbeitet sein
            // eigenes Konto und weiß, dass es existiert (BF-09).
            'validation_groups' => ['Default', 'strict'],
        ]);
    }
}
