<?php

namespace App\Form;

use App\Entity\Infraction;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

class InfractionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ── Type d'infraction ────────────────────────────────
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
                'attr'        => ['class' => 'form-control custom-select'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [new NotBlank(message: 'Le type d\'infraction est obligatoire.')],
            ])

            // ── Montant de l'amende ──────────────────────────────
            ->add('montant_amende', NumberType::class, [
                'label'       => 'Montant amende (DT)',
                'scale'       => 3,
                'attr'        => ['class' => 'form-control', 'placeholder' => '0.000', 'min' => '0', 'step' => '0.001'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [
                    new NotBlank(message: 'Le montant est obligatoire.'),
                    new Positive(message: 'Le montant doit être supérieur à zéro.'),
                ],
            ])

            // ── Lieu ─────────────────────────────────────────────
            ->add('lieu', TextType::class, [
                'label'       => 'Lieu de constatation',
                'attr'        => ['class' => 'form-control', 'placeholder' => 'Ex : Avenue Habib Bourguiba, Tunis'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [new NotBlank(message: 'Le lieu est obligatoire.')],
            ])

            // ── Plaque d'immatriculation ──────────────────────────
            ->add('plaque_immat', TextType::class, [
                'label'      => "Plaque d'immatriculation",
                'attr'       => ['class' => 'form-control', 'placeholder' => 'Ex : 123 TUN 2024', 'maxlength' => 20],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])

            // ── Date et heure de l'infraction ─────────────────────
            ->add('date_infraction', DateTimeType::class, [
                'label'      => 'Date et heure',
                'widget'     => 'single_text',
                'attr'       => ['class' => 'form-control'],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])

            // ── Statut ───────────────────────────────────────────
            ->add('statut', ChoiceType::class, [
                'label'       => 'Statut',
                'placeholder' => '-- Sélectionner --',
                'choices'     => [
                    'À payer'  => 'a_payer',
                    'Payé'     => 'paye',
                    'Contesté' => 'conteste',
                ],
                'attr'        => ['class' => 'form-control custom-select'],
                'label_attr'  => ['class' => 'font-weight-bold'],
                'constraints' => [new NotBlank(message: 'Le statut est obligatoire.')],
            ])

            // ── Notes (optionnel) ─────────────────────────────────
            ->add('notes', TextareaType::class, [
                'label'      => 'Notes',
                'required'   => false,
                'attr'       => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Remarques supplémentaires (optionnel)'],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])

            // ── Citoyen verbalisé (tous les users) ────────────────
            ->add('user', EntityType::class, [
                'class'        => User::class,
                'label'        => 'CIN du citoyen',
                'placeholder'  => '-- Sélectionner un citoyen --',
                'choice_label' => 'cin',
                'required'     => false,
                'attr'         => ['class' => 'form-control custom-select'],
                'label_attr'   => ['class' => 'font-weight-bold'],
            ])

            // ── Agent de police (filtre sur ROLE_POLICE) ──────────
            ->add('agent', EntityType::class, [
                'class'         => User::class,
                'label'         => 'Agent Police',
                'placeholder'   => '-- Sélectionner un agent --',
                'choice_label'  => fn(User $u) => $u->getUserIdentifier(),
                'required'      => false,
                'attr'          => ['class' => 'form-control custom-select'],
                'label_attr'    => ['class' => 'font-weight-bold'],
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_POLICE%')
                        ->orderBy('u.cin', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Infraction::class]);
    }
}
