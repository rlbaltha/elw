<?php

namespace App\Form;

use App\Entity\Doc;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label'  => 'Title'
            ])
            ->add('body', TextareaType::class, [
                'label' => ''
            ])
            ->add('project', HiddenType::class, [
                'required'=>false
            ])
            ->add('stage', HiddenType::class, [
                'required'=>false,
            ])
            ->add('wordcount', HiddenType::class, [
                'label'  => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Doc::class,
        ]);
    }
}
