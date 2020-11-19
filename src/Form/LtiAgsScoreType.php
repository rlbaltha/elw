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
        $lineitems = $options['lineitems'];
        $builder
            ->add('uri', ChoiceType::class, [
                'label'  => 'URI',
                'choices' => $lineitems,
                'choice_value' => 'ltiId',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'lineitems',
        ]);
    }
}
