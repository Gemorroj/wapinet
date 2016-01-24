<?php

namespace Wapinet\MessageBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            ->add('body', TextareaType::class, array('label' => false, 'attr' => array('placeholder' => 'Сообщение')))
        ;
    }

    public function getBlockPrefix()
    {
        return 'wapinet_message_reply_message';
    }
}
