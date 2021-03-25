<?php

namespace App\Form;

use App\Entity\Markupset;
use App\Entity\Rubric;
use App\Entity\Stage;
use App\Entity\LtiAgs;
use App\Repository\LtiAgsRepository;
use App\Repository\MarkupsetRepository;
use App\Repository\RubricRepository;
use App\Repository\StageRepository;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProjectType extends AbstractType
{
    private $ltiAgsRepository;
    private $rubricRepository;
    private $stageRepository;
    private $markupsetRepository;

    public function __construct(RubricRepository $rubricRepository, StageRepository $stageRepository, MarkupsetRepository $markupsetRepository, LtiAgsRepository $ltiAgsRepository)
    {
        $this->stageRepository = $stageRepository;
        $this->rubricRepository = $rubricRepository;
        $this->markupsetRepository = $markupsetRepository;
        $this->ltiAgsRepository = $ltiAgsRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options['options'];
        $user = $this->options['user'] ;
        $courseid = $this->options['courseid'] ;
        $builder
            ->add('name', TextType::class, [
                'label'  => 'Name'
            ])
            ->add('color', TextType::class, [
                'label'  => 'Color'
            ])
            ->add('stages', EntityType::class, [
                'class' => Stage::class,
                'choices' => $this->stageRepository->findByUser($user),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true
            ])
            ->add('rubrics', EntityType::class, [
                'class' => Rubric::class,
                'choices' => $this->rubricRepository->findByUser($user),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true
            ])
            ->add('markupsets', EntityType::class, [
                'class' => Markupset::class,
                'choices' => $this->markupsetRepository->findByUser($user),
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true
            ])
            ->add('lti_grades', EntityType::class, [
                'class' => LtiAgs::class,
                'choices' => $this->ltiAgsRepository->findByCourseid($courseid),
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'options' => null,
        ]);
    }
}
