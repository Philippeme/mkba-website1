<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentLocale = $options['current_locale'] ?? 'fr';

        $builder
            ->add('code', TextType::class, [
                'label' => 'Code du projet',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: PROJ-2024',
                    'pattern' => '[A-Z]{2,4}-[0-9]{4}'
                ],
                'help' => 'Format: XX-0000 (lettres majuscules et chiffres)'
            ])
            
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Développement' => 'development',
                    'Recherche' => 'research',
                    'Infrastructure' => 'infrastructure',
                    'Marketing' => 'marketing',
                    'Autre' => 'other',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une catégorie'
            ])
            
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Faible' => 'low',
                    'Moyenne' => 'medium',
                    'Élevée' => 'high',
                    'Critique' => 'critical',
                ],
                'attr' => ['class' => 'form-select'],
                'data' => 'medium'
            ])
            
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Planification' => 'planning',
                    'En cours' => 'in_progress',
                    'En pause' => 'on_hold',
                    'Terminé' => 'completed',
                    'Annulé' => 'cancelled',
                ],
                'attr' => ['class' => 'form-select'],
                'data' => 'planning'
            ])
            
            ->add('responsible', TextType::class, [
                'label' => 'Responsable',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom du responsable'
                ]
            ])
            
            ->add('department', ChoiceType::class, [
                'label' => 'Département',
                'choices' => [
                    'Informatique' => 'it',
                    'Ressources Humaines' => 'hr',
                    'Finance' => 'finance',
                    'Opérations' => 'operations',
                    'Marketing' => 'marketing',
                    'Recherche & Développement' => 'rd',
                ],
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un département'
            ])
            
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            
            ->add('budget', MoneyType::class, [
                'label' => 'Budget',
                'currency' => 'EUR',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01'
                ]
            ])
            
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'required' => false,
                'data' => 0,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ]
            ])
            
            ->add('isActive', CheckboxType::class, [
                'label' => 'Projet actif',
                'required' => false,
                'data' => true,
                'attr' => ['class' => 'form-check-input']
            ])
            
            // Champs de traduction gérés dynamiquement côté client
            ->add('translation', TranslationType::class, [
                'label' => false,
                'current_locale' => $currentLocale,
                'mapped' => false  // Non mappé car géré manuellement dans le contrôleur
            ])
            
            // Champ caché pour la locale courante
            ->add('currentLocale', HiddenType::class, [
                'data' => $currentLocale,
                'mapped' => false
            ])
            
            // Collection des membres
            ->add('members', CollectionType::class, [
                'entry_type' => MemberType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'prototype_name' => '__member_name__',
                'attr' => ['class' => 'members-collection'],
                'mapped' => false  // Non mappé car géré manuellement dans le contrôleur
            ])
            
            // Collection des liens
            ->add('links', CollectionType::class, [
                'entry_type' => LinkType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'prototype_name' => '__link_name__',
                'attr' => ['class' => 'links-collection'],
                'mapped' => false  // Non mappé car géré manuellement dans le contrôleur
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'current_locale' => 'fr',
            'allow_extra_fields' => true, // Permet les champs extra pour la gestion multilingue
        ]);
        
        $resolver->setAllowedTypes('current_locale', 'string');
    }
}

// Sous-formulaire pour les traductions
class TranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentLocale = $options['current_locale'] ?? 'fr';
        
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du projet',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $currentLocale === 'fr' ? 'Project name' : 'Nom du projet',
                    'data-translatable' => 'true',
                    'data-locale' => $currentLocale
                ],
                'mapped' => false  // Non mappé car géré manuellement
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => $currentLocale === 'fr' ? 'Project description' : 'Description du projet',
                    'data-translatable' => 'true',
                    'data-locale' => $currentLocale
                ],
                'mapped' => false  // Non mappé car géré manuellement
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'current_locale' => 'fr',
            'data_class' => null,  // Pas de classe de données car non mappé
            'inherit_data' => false
        ]);
        
        $resolver->setAllowedTypes('current_locale', 'string');
    }
}

// Sous-formulaire pour les membres
class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom du membre'
                ],
                'mapped' => false
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'email',
                    'placeholder' => 'Email du membre'
                ],
                'mapped' => false
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Développeur' => 'developer',
                    'Designer' => 'designer',
                    'Chef de projet' => 'manager',
                    'Analyste' => 'analyst',
                    'Testeur' => 'tester',
                    'Consultant' => 'consultant',
                ],
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un rôle',
                'mapped' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,  // Pas de classe de données car non mappé
            'inherit_data' => false
        ]);
    }
}

// Sous-formulaire pour les liens
class LinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du lien',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre du lien'
                ],
                'mapped' => false
            ])
            ->add('url', TextType::class, [
                'label' => 'URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'url',
                    'placeholder' => 'https://'
                ],
                'mapped' => false
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Documentation' => 'documentation',
                    'Repository' => 'repository',
                    'Site web' => 'website',
                    'API' => 'api',
                    'Autre' => 'other',
                ],
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Type de lien',
                'mapped' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,  // Pas de classe de données car non mappé
            'inherit_data' => false
        ]);
    }
}