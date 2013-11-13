<?php
namespace Wapinet\Bundle\Form\Type\Icq;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Icq registration
 */
class RegistrationType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);


        $builder->add('reg_type', 'hidden', array('data' => '2'));
        $builder->add('csrf', 'hidden');
        $builder->add('gnm', 'hidden');
        $builder->add('first_name', 'text', array('label' => 'Имя'));
        $builder->add('last_name', 'text', array('label' => 'Фамилия'));
        $builder->add('email', 'email', array('label' => 'E-mail'));
        $builder->add('password', 'password', array('label' => 'Пароль'));
        $builder->add('captcha', 'number', array('label' => 'Код с картинки', 'max_length' => 6));

        $builder->add('submit', 'submit', array('label' => 'Зарегистрироваться'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'icq_user_info';
    }
}
