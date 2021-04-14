<?php

namespace App\Form;

use App\Entity\Card;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label'  => 'Title',
            ])
            ->add('body', CKEditorType::class, [
                'config_name' => 'simple_config',
                'label' => '',
            ])
            ->add('type')
            ->add('type', TextType::class, [
                'label'  => 'Type (lower case and underscore, no spaces, e.g., help_doc)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}
