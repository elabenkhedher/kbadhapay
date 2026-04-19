<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReclamationCitoyenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sujet', TextType::class, [
                'label'       => 'Sujet de la réclamation',
                'attr'        => ['class' => 'form-control', 'placeholder' => 'Ex : Erreur sur mon avis de taxe…', 'maxlength' => 255],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [
                    new NotBlank(message: 'Le sujet est obligatoire.'),
                    new Length(max: 255, maxMessage: 'Le sujet ne peut pas dépasser {{ limit }} caractères.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'       => 'Description détaillée',
                'attr'        => ['class' => 'form-control', 'rows' => 6, 'placeholder' => 'Décrivez votre problème en détail…'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [
                    new NotBlank(message: 'La description est obligatoire.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Reclamation::class]);
    }
}
