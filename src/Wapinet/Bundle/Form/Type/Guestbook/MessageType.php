<?php
namespace Wapinet\Bundle\Form\Type\Guestbook;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

        $builder->add('message', 'textarea', array('attr' => array('placeholder' => 'Сообщение'), 'required' => true, 'label' => false));
        $builder->add('submit', 'submit', array('label' => 'Написать'));
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wapinet\Bundle\Entity\Guestbook',
        ));
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
