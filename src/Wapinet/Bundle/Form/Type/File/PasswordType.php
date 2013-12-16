<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Password
 */
class PasswordType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('password', 'password', array('required' => true, 'label' => 'Пароль'));

        $builder->add('submit', 'submit', array('label' => 'Готово'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'password_form';
    }
}
