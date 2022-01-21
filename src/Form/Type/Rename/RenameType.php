<?php

namespace App\Form\Type\Rename;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RenameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['required' => true, 'label' => false]);
        $builder->add('name', TextType::class, ['label' => 'Новое название']);

        $builder->add('submit', SubmitType::class, ['label' => 'Переименовать']);
    }

    public function getBlockPrefix(): string
    {
        return 'rename';
    }
}
