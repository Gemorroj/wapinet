<?php
namespace Wapinet\Bundle\Form\Type\Guestbook;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Message
 */
class MessageType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('message', 'textarea', array('attr' => array('placeholder' => 'Сообщение'), 'max_length' => 5000, 'required' => true, 'label' => false, 'constraints' => new NotBlank()));
        $builder->add('submit', 'submit', array('label' => 'Написать'));
    }


    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'message_form';
    }
}
