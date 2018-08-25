<?php

namespace App\Form\Type\Rename;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Rename.
 */
class RenameType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['required' => true, 'label' => false]);
        $builder->add('name', TextType::class, ['label' => 'Новое название']);

        $builder->add('submit', SubmitType::class, ['label' => 'Переименовать']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'rename';
    }
}
