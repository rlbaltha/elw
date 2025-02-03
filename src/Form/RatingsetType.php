<?php

namespace App\Form;

use App\Entity\Ratingset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class RatingsetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->options = $options['options'];
        $choices = $this->options['choices'] ;
        $options = array('choices' => $choices);
        $builder->add('rating', CollectionType::class, [
            'entry_type' => RatingType::class,
            'entry_options' => ['options' => $options],
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ratingset::class,
            'options' => null,
        ]);
    }
}
