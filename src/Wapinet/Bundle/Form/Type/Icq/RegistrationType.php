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


        $builder->add('reg_type', 'hidden');
        $builder->add('csrf', 'hidden');
        $builder->add('gnm', 'hidden');
        $builder->add('first_name', 'text', array('label' => 'Имя (1-20 символов)', 'max_length' => 20));
        $builder->add('last_name', 'text', array('label' => 'Фамилия (1-20 символов)', 'max_length' => 20));
        $builder->add('email', 'email', array('label' => 'E-mail'));
        $builder->add('password', 'password', array('label' => 'Пароль (6-8 символов, латиница, цифры)', 'max_length' => 8, 'pattern' => '[a-zA-Z0-9]{6,8}'));
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
