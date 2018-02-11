<?php
namespace WapinetBundle\Form\Type\AudioTags;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use WapinetBundle\Form\Type\FileUrlType;

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

        $builder->add('file', FileUrlType::class, array('accept' => 'audio/*', 'required' => true, 'label' => false));

        $builder->add('submit', SubmitType::class, array('label' => 'Редактировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'audio_tags';
    }
}
