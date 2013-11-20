<?php
namespace Wapinet\Bundle\Form\Type\AudioTags;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Audio Tags Edit
 */
class AudioTagsEditType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('title', 'text', array('required' => false, 'label' => 'Название'));
        $builder->add('album_artist', 'text', array('required' => false, 'label' => 'Исполнитель альбома'));
        $builder->add('artist', 'text', array('required' => false, 'label' => 'Исполнитель'));
        $builder->add('album', 'text', array('required' => false, 'label' => 'Альбом'));
        $builder->add('year', 'text', array('required' => false, 'label' => 'Год'));
        $builder->add('track_number', 'text', array('required' => false, 'label' => 'Номер трека'));
        $builder->add('url_user', 'text', array('required' => false, 'label' => 'Ссылка'));
        $builder->add('genre', 'text', array('required' => false, 'label' => 'Стиль'));
        $builder->add('comment', 'textarea', array('required' => false, 'label' => 'Комментарий'));
        $builder->add('picture', 'file_url', array(
            'attr' => array(
                'accept' => 'image/*'
            ),
            'required' => false,
            'label' => 'Изображение'
        ));
        $builder->add('picture_delete', 'checkbox', array('attr' => array('data-mini' => 'true'), 'required' => false, 'label' => 'Удалить изображение'));

        $builder->add('submit', 'submit', array('label' => 'Отправить'));
    }


    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'audio_tags_edit';
    }
}
