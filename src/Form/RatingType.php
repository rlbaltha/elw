<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options['options'];
        $choices = $this->options['choices'];
        $builder
            ->add('scale', ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'label' => 'Rubric Scale'
            ])
            ->add('comment');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
            'options' => null,
        ]);
    }
}
