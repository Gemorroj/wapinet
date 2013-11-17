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

        $builder->add('text', 'composer', array('required' => false, 'label' => 'Автор'));

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
