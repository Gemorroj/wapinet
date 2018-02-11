<?php

namespace WapinetBundle\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WapinetBundle\Entity\Subscriber;

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
            ->add('emailMessages', CheckboxType::class, array('label' => 'Присылать E-mail о новых сообщениях', 'required' => false))
            ->add('emailNews', CheckboxType::class, array('label' => 'Присылать E-mail о новостях сайта', 'required' => false))
            ->add('emailFriends', CheckboxType::class, array('label' => 'Присылать E-mail о действиях друзей', 'required' => false))
        ;

        $builder->add('submit', SubmitType::class, array('label' => 'Изменить'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Subscriber::class,
        ));
    }

    public function getBlockPrefix()
    {
        return 'wapinet_user_subscriber';
    }
}
