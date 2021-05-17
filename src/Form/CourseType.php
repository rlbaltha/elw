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
use App\Repository\TermRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    private $markupsetRepository;
    private $termRepository;
    public function __construct(MarkupsetRepository $markupsetRepository, TermRepository $termRepository)
    {
        $this->markupsetRepository = $markupsetRepository;
        $this->termRepository = $termRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options['options'];
        $user = $this->options['user'] ;
        $builder
            ->add('name', TextType::class, [
                'label'  => 'Title'
            ])
            ->add('time', TextType::class, [
                'label'  => 'Time'
            ])
            ->add('term', EntityType::class, [
                'class' => Term::class,
                'choices' => $this->termRepository->orderLasttoFirst(),
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false
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
