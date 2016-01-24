<?php

namespace Wapinet\MessageBundle\Form\Type;

use FOS\UserBundle\Form\Type\UsernameFormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\MessageBundle\FormType\NewThreadMessageFormType as BaseType;

/**
 * Message form type for starting a new conversation
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class NewThreadMessageFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('recipient', UsernameFormType::class, array('label' => 'Получатель'))
            ->add('subject', TextType::class, array('label' => 'Тема'))
            ->add('body', TextareaType::class, array('label' => 'Сообщение'))
        ;
    }

    public function getBlockPrefix()
    {
        return 'wapinet_message_new_thread';
    }
}
