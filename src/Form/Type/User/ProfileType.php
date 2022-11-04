<?php

namespace App\Form\Type\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'sex',
                ChoiceType::class,
                [
                    'label' => 'Пол',
                    'required' => false,
                    'choices' => [
                        'Мужской' => User::SEX_MALE,
                        'Женский' => User::SEX_FEMALE,
                    ],
                ]
            )
            ->add('birthday', BirthdayType::class, ['widget' => 'single_text', 'label' => 'Дата рождения', 'required' => false, 'attr' => ['placeholder' => 'ГГГГ-ММ-ДД']])
            ->add('timezone', TimezoneType::class, ['label' => 'Временная зона', 'required' => false])
            ->add('country', CountryType::class, ['label' => 'Страна', 'required' => false])
            ->add('vk', TextType::class, ['label' => 'ID вконтакте', 'required' => false, 'attr' => ['pattern' => '[a-z0-9_]{0,255}', 'placeholder' => 'id123456789']])
            ->add('info', TextareaType::class, ['label' => 'Дополнительная информация', 'required' => false, 'attr' => ['maxlength' => 5000]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'profile',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wapinet_user_profile';
    }
}
