<?php

namespace App\Form;

use App\Entity\LtiAgs;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class LtiAgsScoreType extends AbstractType
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
                'choice_value' => 'lti_id',
                'label'  => 'Grade Lineitem',
            ])
            ->add('userId', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) use ($course) {
                    return $er->createQueryBuilder('u')
                        ->join('u.course', 'c')
                        ->andWhere('c.id = :val')
                        ->setParameter('val', $course->getId());
                },
                'choice_label' => 'lastname',
                'choice_value' => 'lti_id',
                'label'  => 'User',
            ])
            ->add('scoreGiven', NumberType::class, [
                'label'  => 'Score'
            ])
            ->add('scoreMaximum', NumberType::class, [
                'label'  => 'Maximum Score'
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
            'course',
        ]);
    }
}
