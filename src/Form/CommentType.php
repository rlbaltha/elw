<?php

namespace App\Form;

use App\Entity\Comment;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices'  => [
                    'Holistic Feedback' => 'Holistic Feedback',
                    'Revision Plan' => 'Revision Plan'
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-check-inline'],
                'label' => 'Type of Feedback'
            ])
            ->add('body', CKEditorType::class, [
                'config_name' => 'simple_config',
                'label' => ''
            ])
            ->add('grade', TextType::class, [
                'label'  => 'Grade (optional, must be a number)',
                'required' => false
            ])
            ->add('access', ChoiceType::class, [
                'choices'  => [
                    'Hidden' => 'Hidden',
                    'Private' => 'Private',
                    'Shared' => 'Shared'
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-check-inline'],
                'label' => 'Access'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
