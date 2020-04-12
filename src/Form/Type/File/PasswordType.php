<?php

namespace App\Form\Type\File;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as CorePasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Password.
 */
class PasswordType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('password', CorePasswordType::class, ['required' => true, 'label' => 'Пароль', 'constraints' => new NotBlank()]);

        $builder->add('submit', SubmitType::class, ['label' => 'Готово']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'file_password_form';
    }
}
