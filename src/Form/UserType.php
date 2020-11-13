<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Firstname'
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Lastname'
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => ['ROLE_USER' => 'ROLE_USER', 'ROLE_INSTRUCTOR' => 'ROLE_INSTRUCTOR',
                    'ROLE_ADMIN' => 'ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH' => 'ROLE_ALLOWED_TO_SWITCH'],
                'multiple' => true,
                'expanded' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
