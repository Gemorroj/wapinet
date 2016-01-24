<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as CorePasswordType;

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

        $builder->add('password', CorePasswordType::class, array('required' => true, 'label' => 'Пароль', 'constraints' => new NotBlank()));

        $builder->add('submit', SubmitType::class, array('label' => 'Готово'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'file_password_form';
    }
}
