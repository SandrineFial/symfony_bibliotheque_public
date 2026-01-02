<?php

namespace App\Form;

use App\Entity\Books;
use App\Entity\User;
use App\Entity\Themes;
use App\Entity\SousThemes;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bundle\SecurityBundle\Security;

class BookFormType extends AbstractType
{
    private Security $security;
     public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        // titre
        ->add('titre', null, [
            'label' => 'Titre du livre',
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer le titre du livre',
                ]),
                new Length([
                    'min' => 2,
                    'minMessage' => 'Le titre doit comporter au moins {{ limit }} caractères',
                    'max' => 255,
                ]),
            ],
        ])
        // auteur
        ->add('auteur', null, [
            'label' => 'Auteur',
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer le nom de l\'auteur',
                ]),
                new Length([
                    'min' => 2,
                    'minMessage' => 'Le nom de l\'auteur doit comporter au moins {{ limit }} caractères',
                    'max' => 255,
                ]),
            ],
        ])
        // ISBN
        ->add('isbn', null, [
            'label' => 'ISBN',
            'required' => false,
            'constraints' => [
                new Length([                   
                    'max' => 100,
                    'maxMessage' => 'L\'ISBN ne peut pas dépasser {{ limit }} caractères',
                ]),
            ],
        ])
        // nombre de pages
        ->add('nbpages', null, [
            'label' => 'Nombre de pages',
            'required' => false,
            'constraints' => [          
                new Length([
                    'max' => 11,
                    'maxMessage' => 'Le nombre de pages ne peut pas dépasser {{ limit }} caractères',
                ]),
            ],
        ])
        // résumé
        ->add('resume', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Résumé',
            'required' => false,
            'constraints' => [
                new Length([
                    'max' => 1000,
                    'maxMessage' => 'Le résumé ne peut pas dépasser {{ limit }} caractères',
                ]),
            ],
        ])
        // commentaire
        ->add('commentaire', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Commentaire',
            'required' => false,
            'constraints' => [
                new Length([
                    'max' => 1000,
                    'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères',
                ]),
            ],
        ])
        // note (liste déroulante de 1 à 5)
        ->add('note', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'label' => 'Note',
            'required' => false,
            'choices' => [
                '1 (Bof)' => 1,
                '2 (Moyen)' => 2,
                '3 (Bien)' => 3,
                '4 (Très bien)' => 4,
                '5 (Excellent)' => 5,
            ],
            'placeholder' => 'Mettre une note',
        ])
        
        ->add('theme', EntityType::class, [
            'class' => Themes::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->where('t.user = :user')
                    ->setParameter('user', $this->security->getUser())
                    ->orderBy('t.name', 'ASC');
            },
            'label' => 'Thème',
            'required' => true,
            'placeholder' => 'Sélectionnez un thème',
            'choice_label' => 'name',
            'attr' => [
                'id' => 'theme',
                'data-url' => '/get-sous-themes'
            ]
        ])/*
        ->add('sousTheme', EntityType::class, [
            'class' => SousThemes::class,
            'query_builder' => function (EntityRepository $er) {
                // Récupérer tous les sous-thèmes de l'utilisateur pour l'édition
                $user = $this->security->getUser();
                return $er->createQueryBuilder('st')
                    ->join('st.theme', 't')
                    ->where('t.user = :user')
                    ->setParameter('user', $user)
                    ->orderBy('st.name', 'ASC');
            },
            'label' => 'Sous-Thème',
            'required' => false,
            'placeholder' => 'Sélectionnez un sous-thème',
            'choice_label' => 'name',
            // 'mapped' => false, // SUPPRIMER CETTE LIGNE pour que le champ soit lié à l'entité
            'attr' => [
                'id' => 'sous_theme'
            ]
        ])*/
    
        ->add('type', null, [
            'label' => 'Type de document',
            'required' => false,
        ])
        ->add('edition', null, [
            'label' => 'Édition',
            'required' => false,
        ])
        ->add('editionDetail', null, [
            'label' => 'Détail de l\'édition',
            'required' => false,
        ])
        ->add('annees', null, [
            'label' => 'Années d\'édition',
            'required' => false,
        ])
        ->add('etat', null, [
            'label' => 'État',
            'required' => false,
        ])
        ->add('aQui', null, [
            'label' => 'À qui',
            'required' => false,
        ])
        ;// Ajouter les événements pour gérer les sous-thèmes
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $book = $event->getData();
        $this->addSousThemeField($event->getForm(), $book ? $book->getTheme() : null);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $themeId = isset($data['theme']) ? $data['theme'] : null;
        
        $theme = null;
        if ($themeId) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $theme = $user->getThemes()->filter(function($t) use ($themeId) {
                    return $t->getId() == $themeId;
                })->first() ?: null;
            }
        }
        
        $this->addSousThemeField($event->getForm(), $theme);
    }

    private function addSousThemeField(FormInterface $form, ?Themes $theme): void
    {
        $sousThemes = $theme ? $theme->getSousThemes() : [];

        $form->add('sousTheme', EntityType::class, [
            'class' => SousThemes::class,
            'choices' => $sousThemes,
            'label' => 'Sous-Thème',
            'required' => false,
            'placeholder' => $theme ? 'Sélectionnez un sous-thème' : 'Sélectionnez d\'abord un thème',
            'choice_label' => 'name',
            'attr' => [
                'id' => 'sous_theme'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Books::class,
        ]);
    }
}