<?php

namespace App\Form;

use App\Entity\Term;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TermType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('year', TextType::class, [
                'label'  => 'Year (e.g. 2020)'
            ])
            ->add('semester', ChoiceType::class, [
                'choices' => ['Fall' => 'Fall', 'Spring' => 'Spring', 'May' => 'May', 'Summer' => 'Summer'],
                'multiple' => false,
                'expanded' => true
            ])
            ->add('status', ChoiceType::class, [
                'choices' => ['Default' => 'Default', 'Archive' => 'Archive', 'Continuing' => 'Continuing'],
                'multiple' => false,
                'expanded' => true
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Term::class,
        ]);
    }
}
