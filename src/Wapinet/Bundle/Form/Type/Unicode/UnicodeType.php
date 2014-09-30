<?php
namespace Wapinet\Bundle\Form\Type\Unicode;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Unicode
 */
class UnicodeType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('text', 'textarea', array('label' => 'Ваш текст'));
        $builder->add('latin', 'checkbox', array('required' => false, 'label' => 'Заменять на латиницу'));
        $builder->add('zerofill', 'checkbox', array('required' => false, 'label' => 'Удалять лишние нули'));
        $builder->add('html', 'checkbox', array('required' => false, 'label' => 'Преобразовывать специальные символы в HTML-сущности'));

        $builder->add('submit', 'submit', array('label' => 'Перекодировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'unicode';
    }
}
