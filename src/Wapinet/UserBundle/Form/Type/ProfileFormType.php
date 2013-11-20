<?php

namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Wapinet\UserBundle\Entity\User;

class ProfileFormType extends BaseType
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
            ->add('sex', 'choice', array('label' => 'Пол:', 'required' => false, 'choices' => User::getSexChoices()))
            ->add('birthday', 'date', array('widget' => 'single_text', 'label' => 'Дата рождения:', 'required' => false))
            ->add('subscribeComments', 'checkbox', array('label' => 'Присылать E-mail о новых комментариях', 'required' => false))
            ->add('subscribeMessages', 'checkbox', array('label' => 'Присылать E-mail о новых сообщениях', 'required' => false))
            ->add('avatar', 'file_url', array(
                'attr' => array(
                    'accept' => 'image/*',
                    'save' => true,
                    'save_directory' => $this->container->getParameter('wapinet_avatar_dir_filesystem'),
                    'save_public_directory' => $this->container->getParameter('wapinet_avatar_dir_public')
                ),
                'label' => 'Аватар',
                'required' => false,
            ))
            ->add('avatar_delete', 'checkbox', array('mapped' => false, 'attr' => array('data-mini' => 'true'), 'required' => false, 'label' => 'Удалить аватар'));
        ;
    }

    public function getName()
    {
        return 'wapinet_user_profile';
    }
}
