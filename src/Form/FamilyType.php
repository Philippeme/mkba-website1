<?php

namespace App\Form;

use App\Entity\Family;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class FamilyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fname', TextType::class, [
                'label' => 'Family Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter family name'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Enter family description'
                ]
            ])
            ->add('iconBootstrap', ChoiceType::class, [
                'label' => 'Bootstrap Icon',
                'mapped' => false,
                'required' => false,
                'choices' => $this->getBootstrapIcons(),
                'attr' => [
                    'class' => 'form-select',
                    'onchange' => 'updateIconPreview(this.value)'
                ],
                'placeholder' => 'Select a Bootstrap icon'
            ])
            ->add('iconFile', FileType::class, [
                'label' => 'Custom Icon File',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/svg+xml',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, SVG, WebP)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ]
            ])
            ->add('removeIcon', CheckboxType::class, [
                'label' => 'Remove current icon',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Display Order',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ],
                'data' => $options['data']->getDisplayOrder() ?: 0
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'data' => $options['data']->isActive() !== false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Family::class,
        ]);
    }

    private function getBootstrapIcons(): array
    {
        return [
            'Police & Justice' => 'bi-shield-check',
            'Family (Registrar Office)' => 'bi-people-fill',
            'Transport' => 'bi-car-front-fill',
            'Education' => 'bi-mortarboard-fill',
            'Business' => 'bi-building-fill',
            'Public Service' => 'bi-briefcase-fill',
            'Earth & Building' => 'bi-house-fill',
            'Consular Services' => 'bi-globe-americas',
            'Health' => 'bi-heart-pulse-fill',
            'Citizen life' => 'bi-megaphone'
        ];
    }
}