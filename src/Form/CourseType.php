<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Labelset;
use App\Entity\Markup;
use App\Entity\Markupset;
use App\Entity\Term;
use App\Repository\LabelsetRepository;
use App\Repository\MarkupsetRepository;
use App\Repository\ProjectRepository;
use App\Repository\StageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    private $labelsetRepository;
    private $markupsetRepository;
    public function __construct(LabelsetRepository $labelsetRepository, MarkupsetRepository $markupsetRepository)
    {
        $this->labelsetRepository = $labelsetRepository;
        $this->markupsetRepository = $markupsetRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options['options'];
        $user = $this->options['user'] ;
        $builder
            ->add('name', TextType::class, [
                'label'  => 'Title',
                'attr' => ['class' => 'form-control']
            ])
            ->add('time', TextType::class, [
                'label'  => 'Time',
                'attr' => ['class' => 'form-control']
            ])
            ->add('term', EntityType::class, [
                'class' => Term::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('labelsets', EntityType::class, [
                'class' => Labelset::class,
                'choices' => $this->labelsetRepository->findByUser($user),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'radio'],
            ])
            ->add('markupsets', EntityType::class, [
                'class' => Markupset::class,
                'choices' => $this->markupsetRepository->findByUser($user),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'radio'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
            'options' => null,
        ]);
    }
}
