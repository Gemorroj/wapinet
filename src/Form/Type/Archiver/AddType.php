<?php

namespace App\Form\Type\Archiver;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Archiver Add.
 */
class AddType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['required' => true, 'label' => false]);

        $builder->add('submit', SubmitType::class, ['label' => 'Добавить']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'archiver_add';
    }
}
