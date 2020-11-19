<?php

namespace App\Form;

use App\Entity\LtiAgs;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LtiAgsResultsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $course = $options['course'];
        $builder
            ->add('uri', EntityType::class, [
                'class' => LtiAgs::class,
                'query_builder' => function (EntityRepository $er) use ($course) {
                    return $er->createQueryBuilder('l')
                        ->join('l.course', 'c')
                        ->andWhere('c.id = :val')
                        ->setParameter('val', $course->getId());
                },
                'choice_label' => 'label',
                'choice_value' => 'id',
                'label'  => 'Grade Lineitem',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'course',
        ]);
    }
}
