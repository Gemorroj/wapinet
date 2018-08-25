<?php

namespace App\Form\Type\User;

use App\Entity\User;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileType extends BaseType
{
    public function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildUserForm($builder, $options);
        $builder
            ->add(
                'sex',
                ChoiceType::class,
                [
                    'label' => 'Пол',
                    'required' => false,
                    'choices' => \array_flip(User::getSexChoices()),
                ]
            )
            ->add('birthday', BirthdayType::class, ['widget' => 'single_text', 'label' => 'Дата рождения', 'required' => false, 'attr' => ['placeholder' => 'ГГГГ-ММ-ДД']])
            ->add('timezone', TimezoneType::class, ['label' => 'Временная зона', 'required' => false])
            ->add('country', CountryType::class, ['label' => 'Страна', 'required' => false])
            ->add('vk', TextType::class, ['label' => 'ID вконтакте', 'required' => false, 'attr' => ['pattern' => '[a-z0-9_]{0,255}', 'placeholder' => 'id123456789']])
            ->add('info', TextareaType::class, ['label' => 'Дополнительная информация', 'required' => false, 'attr' => ['maxlength' => 5000]])
            ;
    }

    public function getBlockPrefix()
    {
        return 'wapinet_user_profile';
    }
}
