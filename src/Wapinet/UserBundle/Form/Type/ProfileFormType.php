<?php

namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Wapinet\UserBundle\Entity\User;

class ProfileFormType extends BaseType
{
    public function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildUserForm($builder, $options);
        $builder
            ->add('sex', 'choice', array('label' => 'Пол:', 'required' => false, 'choices' => User::getSexChoices()))
            ->add('birthday', 'date', array('widget' => 'single_text', 'label' => 'Дата рождения:', 'required' => false))
            ->add('subscribeComments', 'checkbox', array('label' => 'Присылать E-mail о новых комментариях', 'required' => false))
            ->add('subscribeMessages', 'checkbox', array('label' => 'Присылать E-mail о новых сообщениях', 'required' => false))
            ->add('avatar', 'iphp_file', array('label' => 'Аватар:', 'required' => false))
        ;
    }

    public function getName()
    {
        return 'wapinet_user_profile';
    }
}
