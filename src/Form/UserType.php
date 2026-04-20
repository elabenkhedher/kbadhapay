<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            // ── Numéro CIN ───────────────────────────────────────
            ->add('cin', TextType::class, [
                'label' => 'Numéro CIN',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 8,
                    'pattern' => '[0-9]{8}',
                    'placeholder' => '8 chiffres',
                    'inputmode' => 'numeric',
                ],
                'label_attr' => ['class' => 'font-weight-bold'],
                'constraints' => [
                    new NotBlank(message: 'Le CIN est obligatoire.'),
                    new Length(exactly: 8, exactMessage: 'Le CIN doit contenir exactement {{ limit }} chiffres.'),
                ],
            ])

            // ── Numéro de téléphone ──────────────────────────────
            ->add('telephone', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 8,
                    'pattern' => '[0-9]{8}',
                    'placeholder' => 'Ex : 20123456',
                    'inputmode' => 'tel',
                ],
                'label_attr' => ['class' => 'font-weight-bold'],
                'constraints' => [
                    new Regex(
                        pattern: '/^[0-9]{8}$/',
                        message: 'Le numéro de téléphone doit contenir exactement 8 chiffres.'
                    ),
                ],
            ])

            // ── Adresse Email ─────────────────────────────────────
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : citoyen@gmail.com',
                ],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])

            // ── Rôles ────────────────────────────────────────────
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Citoyen' => 'ROLE_CITOYEN',
                    'Agent Police' => 'ROLE_POLICE',
                    'Agent Kbadha' => 'ROLE_KBADHA',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'label_attr' => ['class' => 'font-weight-bold d-block mb-1'],
            ])

            // ── Mot de passe (non mappé — haché dans le controller) ──
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                'label_attr' => ['class' => 'font-weight-bold'],
                'constraints' => $isEdit ? [] : [
                    new NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,    // Passer true lors de l'édition
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}