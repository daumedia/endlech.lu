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
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.name',
                'attr' => ['placeholder' => 'form.name_placeholder', 'autocomplete' => 'name'],
                'constraints' => [
                    new NotBlank(message: 'user.name_blank'),
                    new Length(min: 2, max: 100, minMessage: 'user.name_min'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'attr' => ['placeholder' => 'form.email_placeholder', 'autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(message: 'user.email_blank'),
                    new Email(message: 'user.email_invalid'),
                ],
            ])
            ->add('avatar', FileType::class, [
                'label' => 'profile.form.avatar',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        maxSizeMessage: 'profile.avatar_max_size',
                        mimeTypesMessage: 'profile.avatar_mime_type',
                    ),
                ],
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
