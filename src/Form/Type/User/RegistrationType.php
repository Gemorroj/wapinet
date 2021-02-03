<?php

namespace App\Form\Type\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => true])
            ->add('username', null, [
                'attr' => [
                    'minlength' => 3,
                    'maxlength' => 180,
                ],
                'label' => 'Username',
                'required' => true,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'required' => true,
                'constraints' => [new Length(['min' => 6])],
                'type' => PasswordType::class,
                'attr' => [
                    'minlength' => 6,
                    'autocomplete' => 'new-password',
                ],
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Confirm password'],
                'invalid_message' => 'Password and confirmation didn\'t match',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'registration',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'user_registration';
    }
}
