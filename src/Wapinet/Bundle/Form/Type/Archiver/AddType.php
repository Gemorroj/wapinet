<?php
namespace Wapinet\Bundle\Form\Type\Archiver;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Archiver Add
 */
class AddType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('required' => true, 'label' => 'Файл добавляемый в архив'));

        $builder->add('submit', 'submit', array('label' => 'Добавить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'archiver_add';
    }
}
