<?php

namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
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
            ->add('sex', 'choice', array('label' => 'Пол', 'required' => false, 'choices' => User::getSexChoices()))
            ->add('birthday', 'birthday', array('widget' => 'single_text', 'label' => 'Дата рождения', 'required' => false, 'attr' => array('placeholder' => 'ГГГГ-ММ-ДД')))
            ->add('timezone', 'timezone', array('label' => 'Временная зона', 'required' => false))
            ->add('country', 'country', array('label' => 'Страна', 'required' => false))
            ->add('info', 'textarea', array('label' => 'Дополнительная информация', 'required' => false, 'attr' => array('maxlength' => 5000)))
            ->add('avatar', 'file_url', array(
                'accept' => 'image/*',
                'label' => 'Аватар',
                'required' => false,
                'delete_button' => true,
            ));
        ;
    }

    public function getName()
    {
        return 'wapinet_user_profile';
    }
}
