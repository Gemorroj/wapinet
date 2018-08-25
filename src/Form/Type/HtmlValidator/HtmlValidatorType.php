<?php

namespace App\Form\Type\HtmlValidator;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * HtmlValidator.
 */
class HtmlValidatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('html', TextareaType::class, [
            'label' => 'HTML код',
            'required' => false,
        ]);
        $builder->add('file', FileUrlType::class, [
            'label' => false,
            'required' => false,
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Проверить']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'html_validator';
    }
}
