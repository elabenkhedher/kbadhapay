<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idDocumentPath', FileType::class, [
                'label'       => 'National ID Card',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new File(
                        maxSize: '5120k',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'application/pdf',
                        ],
                        maxSizeMessage: 'Your ID document is too large (max 5MB).',
                        mimeTypesMessage: 'Please upload a valid image (JPEG, PNG, WEBP) or PDF.',
                    ),
                ],
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp,application/pdf',
                ],
                'help' => 'Accepted formats: JPEG, PNG, WEBP, PDF — max 5 MB.',
            ])
            ->add('passportPath', FileType::class, [
                'label'       => 'Passport',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new File(
                        maxSize: '5120k',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'application/pdf',
                        ],
                        maxSizeMessage: 'Your passport file is too large (max 5MB).',
                        mimeTypesMessage: 'Please upload a valid image (JPEG, PNG, WEBP) or PDF.',
                    ),
                ],
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp,application/pdf',
                ],
                'help' => 'Accepted formats: JPEG, PNG, WEBP, PDF — max 5 MB.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr'       => [
                'enctype'    => 'multipart/form-data',
                'novalidate' => 'novalidate',
            ],
        ]);
    }
}