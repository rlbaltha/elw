<?php

namespace App\Form;

use App\Entity\Doc;
use App\Entity\Label;
use App\Entity\Labelset;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('color')
            ->add('level')
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'multiple' => false,
            ])
            ->add('labelset', EntityType::class, [
                'class' => Labelset::class,
                'choice_label' => 'name',
                'multiple' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Label::class,
        ]);
    }
}
