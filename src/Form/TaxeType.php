<?php

namespace App\Form;

use App\Entity\Taxe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class TaxeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_taxe', TextType::class, [
                'label'       => 'Nom de la taxe',
                'attr'        => ['placeholder' => 'Ex : Taxe foncière, Taxe sur les enseignes…', 'class' => 'form-control'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [new NotBlank(message: 'Le nom de la taxe est obligatoire.')],
            ])
            ->add('description', TextareaType::class, [
                'label'      => 'Description',
                'required'   => false,
                'attr'       => ['placeholder' => 'Description de la taxe (optionnel)', 'class' => 'form-control', 'rows' => 4],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])
            ->add('montant', NumberType::class, [
                'label'       => 'Montant (DT)',
                'scale'       => 3,
                'attr'        => ['placeholder' => '0.000', 'class' => 'form-control', 'min' => '0', 'step' => '0.001'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [
                    new NotBlank(message: 'Le montant est obligatoire.'),
                    new Positive(message: 'Le montant doit être supérieur à zéro.'),
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label'      => 'Taxe active',
                'required'   => false,
                'attr'       => ['class' => 'custom-control-input'],
                'label_attr' => ['class' => 'custom-control-label font-weight-bold'],
                'row_attr'   => ['class' => 'form-group custom-control custom-switch ml-1 mt-2'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Taxe::class]);
    }
}
