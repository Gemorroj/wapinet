<?php

namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SubscriberType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('emailComments', 'checkbox', array('label' => 'Присылать E-mail о новых комментариях', 'required' => false))
            ->add('emailMessages', 'checkbox', array('label' => 'Присылать E-mail о новых сообщениях', 'required' => false))
            ->add('emailNews', 'checkbox', array('label' => 'Присылать E-mail о новостях сайта', 'required' => false))
            ->add('emailFriends', 'checkbox', array('label' => 'Присылать E-mail о действиях друзей', 'required' => false))
        ;

        $builder->add('submit', 'submit', array('label' => 'Изменить'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wapinet\UserBundle\Entity\Subscriber',
        ));
    }

    public function getName()
    {
        return 'wapinet_user_subscriber';
    }
}