<?php

namespace App\Form\Type\AudioTags;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class AudioTagsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['accept' => 'audio/*', 'required' => true, 'label' => false]);

        $builder->add('submit', SubmitType::class, ['label' => 'Редактировать']);
    }

    public function getBlockPrefix(): string
    {
        return 'audio_tags';
    }
}
