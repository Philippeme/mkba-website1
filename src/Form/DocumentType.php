<?php

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du document',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisissez le nom du document',
                    'maxlength' => 255
                ],
                'help' => 'Nom descriptif du document (max. 255 caractères)'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez le contenu et l\'usage du document'
                ],
                'help' => 'Description détaillée du document et de son utilisation'
            ])
            ->add('uploadedFile', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'image/svg+xml',
                            'text/plain',
                            'text/csv',
                            'application/zip',
                            'application/x-rar-compressed'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier valide (PDF, Word, Excel, PowerPoint, Images, Text, ZIP, RAR)',
                        'maxSizeMessage' => 'Le fichier ne peut pas dépasser {{ limit }}{{ suffix }}'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.svg,.txt,.csv,.zip,.rar',
                    'onchange' => 'previewFile(this)'
                ],
                'help' => 'Fichiers acceptés: PDF, Office, Images, Texte, Archives (max. 10MB)'
            ])
            ->add('removeFile', CheckboxType::class, [
                'label' => 'Supprimer le fichier existant',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Cochez pour supprimer le fichier actuellement associé au document',
                // Ne pas afficher ce champ lors de la création
                'row_attr' => [
                    'style' => $options['data']->getId() ? '' : 'display: none;'
                ]
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => 1
                ],
                'data' => $options['data']->getDisplayOrder() ?: 0,
                'help' => 'Ordre d\'affichage du document (0 = premier)'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Document actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'data' => $options['data']->isActive() !== false,
                'help' => 'Un document inactif n\'est pas visible dans les listes publiques'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'attr' => [
                'novalidate' => 'novalidate' // HTML5 validation désactivée pour utiliser celle de Symfony
            ]
        ]);
    }
}