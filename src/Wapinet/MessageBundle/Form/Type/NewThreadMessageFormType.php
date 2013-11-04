<?php

namespace Wapinet\MessageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
            ->add('recipient', 'fos_user_username', array('label' => 'Получатель'))
            ->add('subject', 'text', array('label' => 'Тема'))
            ->add('body', 'textarea', array('label' => 'Сообщение'))
        ;
    }

    public function getName()
    {
        return 'wapinet_message_new_thread';
    }
}
