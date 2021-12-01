<?php

namespace App\Form;

use App\Entity\LtiAgs;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class LtiAgsScoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $comment = $options['comment'];
        $score = $options['score'];
        $column = $options['column'];
        $uris = $options['uris'];
        $builder
            ->add('uri', EntityType::class, [
                'class' => LtiAgs::class,
                'choices' => $uris,
                'choice_label' => 'label',
                'choice_value' => 'id',
                'label'  => 'eLC Grade Column',
                'expanded' => true,
                'data' => $column
            ])
            ->add('scoreGiven', NumberType::class, [
                'label'  => 'Grade (must be a number)',
                'data' => $score
            ])
            ->add('comment', TextareaType::class, [
                'attr' => ['rows' => '10'],
                'label' => '',
                'required' => false,
                'data' => $comment
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'comment',
            'score',
            'column',
            'uris'
        ]);
    }
}
