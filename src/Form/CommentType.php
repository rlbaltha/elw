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
                    'Ask a Question' => 'Question',
                    'Give Holistic Feedback' => 'Holistic Feedback',
                    'Post a Revision Plan' => 'Revision Plan',
                ],
                'expanded' => true,
                'label' => 'Type of Comment'
            ])
            ->add('body', CKEditorType::class, [
                'config_name' => 'simple_config',
                'label' => ''
            ])
            ->add('access', ChoiceType::class, [
                'choices'  => [
                    'Hidden' => 'Hidden',
                    'Private' => 'Private'
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
