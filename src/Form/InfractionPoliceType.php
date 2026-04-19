<?php

namespace App\Form;

use App\Entity\Infraction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Formulaire infraction pour agents police.
 * N'expose pas les champs user/agent : ils sont assignés par le controller.
 */
class InfractionPoliceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type_infraction', ChoiceType::class, [
                'label'       => "Type d'infraction",
                'placeholder' => '-- Sélectionner --',
                'choices'     => [
                    'Radar'         => 'radar',
                    'Feu rouge'     => 'feu_rouge',
                    'Stationnement' => 'stationnement',
                    'Téléphone'     => 'telephone',
                    'Alcool'        => 'alcool',
                    'Ceinture'      => 'ceinture',
                ],
                'expanded'    => true,   // boutons radio natifs (remplacés en CSS cards)
                'multiple'    => false,
                'label_attr'  => ['class' => 'font-weight-bold d-block mb-2'],
                'constraints' => [new NotBlank(message: "Le type d'infraction est obligatoire.")],
            ])
            ->add('lieu', TextType::class, [
                'label'       => 'Lieu de constatation',
                'attr'        => ['class' => 'form-control', 'placeholder' => 'Ex : Av. Habib Bourguiba, Tunis'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [new NotBlank(message: 'Le lieu est obligatoire.')],
            ])
            ->add('plaque_immat', TextType::class, [
                'label'      => "Plaque d'immatriculation",
                'attr'       => ['class' => 'form-control text-uppercase', 'placeholder' => 'Ex : 123 TUN 2025', 'maxlength' => 20],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])
            ->add('montant_amende', NumberType::class, [
                'label'       => 'Montant de l\'amende (DT)',
                'scale'       => 3,
                'attr'        => ['class' => 'form-control', 'placeholder' => '0.000', 'min' => '0', 'step' => '0.001'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [
                    new NotBlank(message: 'Le montant est obligatoire.'),
                    new Positive(message: 'Le montant doit être supérieur à zéro.'),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label'      => 'Notes (optionnel)',
                'required'   => false,
                'attr'       => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Observations supplémentaires…'],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Infraction::class]);
    }
}
