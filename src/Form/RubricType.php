<?php

namespace App\Form;

use App\Entity\Rubric;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RubricType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label'  => 'Name'
            ])
            ->add('sort', IntegerType::class)
            ->add('body', TextareaType::class, [
                'required' => false,
                'label' => ''
            ])
            ->add('level', ChoiceType::class, [
                'choices' => ['Default' => '0', 'Instructor Created' => '1', 'Shared' => '2','Archived' => '3'],
                'multiple' => false,
                'expanded' => true,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rubric::class,
        ]);
    }
}
