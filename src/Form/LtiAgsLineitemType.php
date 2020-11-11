<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LtiAgsLineitemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('scoreMaximum', TextType::class, [
                'label'  => 'Maximum Score',
                'attr' => ['class' => 'form-control']
            ])
            ->add('label', TextType::class, [
                'label'  => 'Grade Label',
                'attr' => ['class' => 'form-control']
            ])
            ->add('resourceId', TextType::class, [
                'label'  => 'Resource Id',
                'attr' => ['class' => 'form-control']
            ])
            ->add('tag', TextType::class, [
                'label'  => 'Tag',
                'attr' => ['class' => 'form-control']
            ])
            ->add('startDateTime', TextType::class, [
                'label'  => 'Start Time',
                'attr' => ['class' => 'form-control'],
                'required' => false
            ])
            ->add('endDateTime', TextType::class, [
                'label'  => 'End Time',
                'attr' => ['class' => 'form-control'],
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
