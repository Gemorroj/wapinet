<?php

namespace App\Form\Type\Obfuscator;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ObfuscatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('code', TextareaType::class, ['trim' => true, 'label' => 'Ваш PHP код']);
        $builder->add('remove_comments', CheckboxType::class, ['data' => true, 'required' => false, 'label' => 'Удалить комментарии']);
        $builder->add('remove_spaces', CheckboxType::class, ['data' => true, 'required' => false, 'label' => 'Удалять лишние пробелы']);

        $builder->add('submit', SubmitType::class, ['label' => 'Обфусцировать']);
    }

    public function getBlockPrefix(): string
    {
        return 'obfuscator';
    }
}
