<?php
namespace Wapinet\Bundle\Form\Type\Icq;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\Bundle\Form\Type\Email\EmailType;
use Wapinet\Bundle\Form\Type\File\PasswordType;

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

        $builder->add('reg_type', HiddenType::class);
        $builder->add('csrf', HiddenType::class);
        $builder->add('gnm', HiddenType::class);
        $builder->add('first_name', TextType::class, array('label' => 'Имя (1-20 символов)', 'attr' => array('maxlength' => 20)));
        $builder->add('last_name', TextType::class, array('label' => 'Фамилия (1-20 символов)', 'attr' => array('maxlength' => 20)));
        $builder->add('email', EmailType::class, array('label' => 'E-mail'));
        $builder->add('password', PasswordType::class, array('label' => 'Пароль (6-8 символов, латиница, цифры)', 'attr' => array('pattern' => '[a-zA-Z0-9]{6,8}', 'maxlength' => 20)));
        $builder->add('captcha', NumberType::class, array('label' => 'Код с картинки', 'attr' => array('maxlength' => 6)));

        $builder->add('submit', SubmitType::class, array('label' => 'Зарегистрироваться'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'icq_user_info';
    }
}
