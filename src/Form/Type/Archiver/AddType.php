<?php

namespace App\Form\Type\Archiver;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class AddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['required' => true, 'label' => false]);

        $builder->add('submit', SubmitType::class, ['label' => 'Добавить']);
    }

    public function getBlockPrefix(): string
    {
        return 'archiver_add';
    }
}
