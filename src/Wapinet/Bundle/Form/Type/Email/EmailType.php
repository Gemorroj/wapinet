<?php
namespace Wapinet\Bundle\Form\Type\Email;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Email
 */
class EmailType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('to', 'email', array('label' => 'Кому', 'data' => '@'));
        $builder->add('from', 'email', array('label' => 'От кого', 'data' => '@'));
        $builder->add('subject', 'text', array('label' => 'Тема'));
        $builder->add('message', 'textarea', array('label' => 'Сообщение'));
        $builder->add('attach', 'file', array('label' => 'Файл', 'required' => false));
        $builder->add('url', 'url', array('label' => 'Файл', 'required' => false));
        //$builder->add('captcha', 'captcha');

        $builder->add('submit', 'submit', array('label' => 'Отправить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'email_form';
    }
}
