<?php
namespace Wapinet\Bundle\Form\Type\Rename;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Rename
 */
class RenameType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('required' => true, 'label' => false));
        $builder->add('name', 'text', array('label' => 'Новое название'));

        $builder->add('submit', 'submit', array('label' => 'Переименовать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'rename';
    }
}
