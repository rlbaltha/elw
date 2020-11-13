<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LtiAgsLineitemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('scoreMaximum', TextType::class, [
                'label'  => 'Maximum Score'
            ])
            ->add('label', TextType::class, [
                'label'  => 'Grade Label'
            ])
            ->add('resourceId', TextType::class, [
                'label'  => 'Resource Id'
            ])
            ->add('tag', TextType::class, [
                'label'  => 'Tag',
                'required' => false
            ])
            ->add('startDateTime', DateTimeType::class, [
                'label'  => 'Start Time',
                'required' => false
            ])
            ->add('endDateTime', DateTimeType::class, [
                'label'  => 'End Time',
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
