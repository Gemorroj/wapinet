<?php
namespace Wapinet\Bundle\Form\Type\AudioTags;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Audio Tags
 */
class AudioTagsType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('accept' => 'audio/*', 'required' => true, 'label' => false));

        $builder->add('submit', 'submit', array('label' => 'Редактировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'audio_tags';
    }
}
