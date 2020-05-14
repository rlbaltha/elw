<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Labelset;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelsetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label'  => 'Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('level', ChoiceType::class, [
                'choices' => ['Default' => '0', 'Instructor Created' => '1'],
                'multiple' => false,
                'expanded' => true,
                'attr' => ['class' => 'checkbox'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Labelset::class,
        ]);
    }
}
