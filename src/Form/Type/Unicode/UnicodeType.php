<?php

namespace App\Form\Type\Unicode;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class UnicodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('text', TextareaType::class, ['label' => 'Ваш текст']);
        $builder->add('latin', CheckboxType::class, ['required' => false, 'label' => 'Заменять на латиницу']);
        $builder->add('zerofill', CheckboxType::class, ['required' => false, 'label' => 'Удалять лишние нули']);
        $builder->add('html', CheckboxType::class, ['required' => false, 'label' => 'Преобразовывать специальные символы в HTML-сущности']);

        $builder->add('submit', SubmitType::class, ['label' => 'Перекодировать']);
    }

    public function getBlockPrefix(): string
    {
        return 'unicode';
    }
}
