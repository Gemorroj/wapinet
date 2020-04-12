<?php

namespace App\Form\Type\AudioTags;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Audio Tags.
 */
class AudioTagsType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['accept' => 'audio/*', 'required' => true, 'label' => false]);

        $builder->add('submit', SubmitType::class, ['label' => 'Редактировать']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'audio_tags';
    }
}
