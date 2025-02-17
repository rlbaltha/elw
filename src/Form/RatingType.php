<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;

class RatingType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('comment')
            ->addEventListener(FormEvents::PRE_SET_DATA, function ($event) {
                $rating = $event->getData();
                $level = $rating->getRubric()->getLevel();
                $form = $event->getForm();
                if ($level == 0) {
                    $choices = ['1: Unengaged with the writing process or no evidence of revision.' => '1',
                        '2: Minimally engaged with the writing process.' => '2',
                        ' 3: Somewhat engaged with the writing process.' => '3',
                        '4: Highly engaged with the writing process.' => '4',
                        '5: Exemplary engagement with the draft structure or revision process.' => '5',];
                    $form->add('scale', ChoiceType::class, [
                        'choices' => $choices, 'expanded' => true, 'label' => 'Rubric Scale']);
                } else {
                    $choices = ['1: Holistic revision necessary. The student may need to revise the full
                           document.' => '1',
                        '2: Substantial revision necessary. The student may need to revise elements in a majority of the document.' => '2',
                        ' 3: Some revision necessary. The student may need to rethink or
                           restructure one or more paragraphs or large sections.' => '3',
                        '4: Slight revision necessary. Some adjustments on the sentence or
                       paragraph level would help the document stand out.' => '4',
                        '5: No revision necessary. The document is exemplary as it stands. While
                      further improvement is still (and always) possible, time would be better
                      spent elsewhere.' => '5',];;
                    $form->add('scale', ChoiceType::class, [
                        'choices' => $choices, 'expanded' => true, 'label' => 'Rubric Scale']);
                }
            });

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rating::class
        ]);
    }
}
