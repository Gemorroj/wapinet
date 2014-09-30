<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;

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

        $builder->add('password', 'password', array('required' => true, 'label' => 'Пароль', 'constraints' => new NotBlank()));

        $builder->add('submit', 'submit', array('label' => 'Готово'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'file_password_form';
    }
}
