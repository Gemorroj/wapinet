<?php

namespace App\Form\Type\User;

use App\Entity\Subscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('emailNews', CheckboxType::class, ['label' => 'Присылать E-mail о новостях сайта', 'required' => false])
            ->add('emailFriends', CheckboxType::class, ['label' => 'Присылать E-mail о действиях друзей', 'required' => false])
        ;

        $builder->add('submit', SubmitType::class, ['label' => 'Изменить']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subscriber::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wapinet_user_subscriber';
    }
}
