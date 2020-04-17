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
                    'Holistic' => 'Holistic',
                    'Review Plan' => 'Review Plan',
                ],
                'expanded' => true,
                'attr' => ['class' => 'radio'],
                'label' => 'Type of Feedback'
            ])
            ->add('body', CKEditorType::class, [
                'config_name' => 'simple_config',
                'label' => '',
            ])
            ->add('grade', TextType::class, [
                'label'  => 'Grade',
                'attr' => ['class' => 'form-control']
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
