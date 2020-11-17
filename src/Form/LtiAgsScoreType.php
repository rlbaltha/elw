<?php

namespace App\Form;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LtiAgsScoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('uri', TextType::class, [
                'label'  => 'URI'
            ])
            ->add('userId', TextType::class, [
                'label'  => 'Userid'
            ])
            ->add('scoreGiven', NumberType::class, [
                'label'  => 'Score'
            ])
            ->add('scoreMaximum', NumberType::class, [
                'label'  => 'Score'
            ])
            ->add('comment', CKEditorType::class, [
                'config_name' => 'simple_config',
                'label' => '',
                'required' => false
            ])
            ->add('activityProgress', ChoiceType::class, [
                'choices'  => [
                    'Initialized' => 'Initialized',
                    'Started' => 'Started',
                    'InProgress' => 'InProgress',
                    'Submitted' => 'Submitted',
                    'Completed' => 'Completed',
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-check-inline'],
                'label' => 'Activitity Progress'
            ])
            ->add('gradingProgress', ChoiceType::class, [
                'choices'  => [
                    'NotReady' => 'NotReady',
                    'Failed' => 'Failed',
                    'Pending' => 'Pending',
                    'PendingManual' => 'PendingManual',
                    'FullyGraded' => 'FullyGraded',
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-check-inline'],
                'label' => 'Grading Progress'
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
