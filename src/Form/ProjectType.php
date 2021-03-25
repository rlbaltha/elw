<?php

namespace App\Form;

use App\Entity\Markupset;
use App\Entity\Rubricset;
use App\Entity\Stage;
use App\Repository\MarkupsetRepository;
use App\Repository\RubricsetRepository;
use App\Repository\StageRepository;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProjectType extends AbstractType
{
    private $rubricsetRepository;
    private $stageRepository;
    private $markupsetRepository;

    public function __construct(RubricsetRepository $rubricsetRepository, StageRepository $stageRepository, MarkupsetRepository $markupsetRepository)
    {
        $this->stageRepository = $stageRepository;
        $this->rubricsetRepository = $rubricsetRepository;
        $this->markupsetRepository = $markupsetRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options['options'];
        $user = $this->options['user'] ;
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
            ->add('rubricset', EntityType::class, [
                'class' => Rubricset::class,
                'choices' => $this->rubricsetRepository->findByUser($user),
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => true
            ])
            ->add('markupsets', EntityType::class, [
                'class' => Markupset::class,
                'choices' => $this->markupsetRepository->findByUser($user),
                'choice_label' => 'name',
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
