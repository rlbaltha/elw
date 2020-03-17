<?php

namespace App\Form;

use App\Entity\Markup;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use App\Entity\Markupset;

class MarkupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label'  => 'Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('color', TextType::class, [
                'label'  => 'Color',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', CKEditorType::class, [
                'config_name' => 'simple_config',
                'label' => '',
            ])
            ->add('sort', TextType::class, [
                'label'  => 'Sort',
                'attr' => ['class' => 'form-control']
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'multiple' => false,
            ])
            ->add('markupset', EntityType::class, [
                'class' => Markupset::class,
                'choice_label' => 'name',
                'multiple' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Markup::class,
        ]);
    }
}
