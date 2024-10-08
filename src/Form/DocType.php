<?php

namespace App\Form;

use App\Entity\Doc;
use App\Entity\Project;
use App\Entity\Stage;
use App\Repository\ProjectRepository;
use App\Repository\StageRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options['options'];
        $choices = $this->options['choices'] ;
        $stages = $this->options['stages'];
        $builder
            ->add('title', TextType::class, [
                'label'  => 'Title',
                'label_attr' => ['class' => 'visually-hidden'],
            ])
            ->add('stage', EntityType::class, [
                'class' => Stage::class,
                'choices' => $stages,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => true,
                'attr' => ['class' => 'form-check-inline'],
                'label' => 'Stage',
                'label_attr' => ['class' => 'radio-inline'],
                'required' => 'true'
            ])
            ->add('access', ChoiceType::class, [
                'choices' => $choices,
                'multiple' => false,
                'expanded' => true,
                'attr' => ['class' => 'form-check-inline'],
                'label' => 'Access',
                'label_attr' => ['class' => 'radio-inline'],
                'required' => 'true'
            ])
            ->add('wordcount', HiddenType::class, [
                'label'  => '',
                'label_attr' => ['class' => 'd-inline'],
            ])
            ->add('body', TextareaType::class, [
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Doc::class,
            'options' => null,
        ]);
    }
}
