<?php

namespace Wapinet\MessageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\MessageBundle\FormType\ReplyMessageFormType as BaseType;

/**
 * Form type for a reply
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class ReplyMessageFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('body', 'textarea', array('label' => false, 'attr' => array('placeholder' => 'Сообщение')))
        ;
    }

    public function getName()
    {
        return 'wapinet_message_reply_message';
    }
}
