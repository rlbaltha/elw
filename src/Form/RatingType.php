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
        $builder
            ->add('scale', ChoiceType::class, [
                'choices' => [
                    '1: Holistic revision necessary. The student may need to revise the full
                    document.' => '1',
                    '2: Substantial revision necessary. The student may need to revise.' => '2',
                    ' 3: Some revision necessary. The student may need to rethink or
                    restructure one or more paragraphs or large sections.' => '3',
                    '4: Slight revision necessary. Some adjustments on the sentence or
                    paragraph level would help the document stand out.' => '4',
                    '5: No revision necessary. The document is exemplary as it stands. While
                    further improvement is still (and always) possible, time would be better
                    spent elsewhere.' => '5',
                ],
                'expanded' => true,
                'label' => 'Rubric Scale'
            ])
            ->add('comment');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}
