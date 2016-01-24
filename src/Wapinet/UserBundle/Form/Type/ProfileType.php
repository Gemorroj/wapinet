<?php

namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Wapinet\UploaderBundle\Form\Type\FileUrlType;
use Wapinet\UserBundle\Entity\User;

class ProfileType extends BaseType
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param string $user
     * @param ContainerInterface $container
     */
    public function __construct($user, ContainerInterface $container)
    {
        parent::__construct($user);
        $this->container = $container;
    }

    public function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildUserForm($builder, $options);
        $builder
            ->add('sex', ChoiceType::class, array('label' => 'Пол', 'required' => false, 'choices' => User::getSexChoices()))
            ->add('birthday', BirthdayType::class, array('widget' => 'single_text', 'label' => 'Дата рождения', 'required' => false, 'attr' => array('placeholder' => 'ГГГГ-ММ-ДД')))
            ->add('timezone', TimezoneType::class, array('label' => 'Временная зона', 'required' => false))
            ->add('country', CountryType::class, array('label' => 'Страна', 'required' => false))
            ->add('vk', TextType::class, array('label' => 'ID вконтакте', 'required' => false, 'attr' => array('pattern' => '[a-z0-9_]{0,255}', 'placeholder' => 'id123456789')))
            ->add('info', TextareaType::class, array('label' => 'Дополнительная информация', 'required' => false, 'attr' => array('maxlength' => 5000)))
            ->add('avatar', FileUrlType::class, array(
                'accept' => 'image/*',
                'label' => 'Аватар',
                'required' => false,
                'delete_button' => true,
            ));
        ;
    }

    public function getBlockPrefix()
    {
        return 'wapinet_user_profile';
    }
}
