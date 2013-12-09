<?php
namespace Wapinet\Bundle\Form\Type\Files;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Upload
 */
class UploadType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('required' => true, 'label' => false));
        $builder->add('description', 'textarea', array('required' => false, 'label' => 'Описание'));
        $builder->add('password', 'password', array('required' => false, 'label' => 'Пароль'));

        $builder->add('submit', 'submit', array('label' => 'Загрузить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'upload_form';
    }
}
