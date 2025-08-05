<?php

namespace App\Form;

use App\Entity\Books;
use App\Entity\User;
use App\Entity\Themes;
use App\Entity\SousThemes;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BookFormType extends AbstractType
{
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
            'choice_label' => 'name',
            'label' => 'Thème',
            'required' => true,
            'placeholder' => 'Sélectionnez un thème',
            'query_builder' => function(\App\Repository\ThemesRepository $tr) {
                return $tr->createQueryBuilder('t')->orderBy('t.name', 'ASC');
            },
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Books::class,
        ]);
    }
}