<?php

namespace App\Form\Type\AudioTags;

use App\Form\Type\FileUrlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Audio Tags Edit.
 */
class AudioTagsEditType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('title', TextType::class, ['required' => false, 'label' => 'Название']);
        $builder->add('album_artist', TextType::class, ['required' => false, 'label' => 'Исполнитель альбома']);
        $builder->add('artist', TextType::class, ['required' => false, 'label' => 'Исполнитель']);
        $builder->add('album', TextType::class, ['required' => false, 'label' => 'Альбом']);
        $builder->add('year', TextType::class, ['required' => false, 'label' => 'Год']);
        $builder->add('track_number', TextType::class, ['required' => false, 'label' => 'Номер трека']);
        $builder->add('url_user', TextType::class, ['required' => false, 'label' => 'Ссылка']);
        $builder->add('genre', TextType::class, ['required' => false, 'label' => 'Стиль']);
        $builder->add('comment', TextareaType::class, ['required' => false, 'label' => 'Комментарий']);
        $builder->add('picture', FileUrlType::class, [
            'delete_button' => true,
            'accept' => 'image/*',
            'required' => false,
            'label' => 'Изображение',
        ]);
        $builder->add('remove_other_tags', CheckboxType::class, ['required' => false, 'label' => 'Удалить другие тэги']);

        $builder->add('submit', SubmitType::class, ['label' => 'Редактировать']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'audio_tags_edit';
    }
}
