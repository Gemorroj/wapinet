<?php

namespace App\Form\Type\HtmlValidator;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class HtmlValidatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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

    public function getBlockPrefix(): string
    {
        return 'html_validator';
    }
}
