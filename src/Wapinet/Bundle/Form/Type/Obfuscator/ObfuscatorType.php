<?php
namespace Wapinet\Bundle\Form\Type\Obfuscator;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Obfuscator
 */
class ObfuscatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('code', 'textarea', array('trim' => true, 'label' => 'Ваш PHP код'));
        $builder->add('remove_comments', 'checkbox', array('data' => true, 'required' => false, 'label' => 'Удалить комментарии'));
        $builder->add('remove_spaces', 'checkbox', array('data' => true, 'required' => false, 'label' => 'Удалять лишние пробелы'));

        $builder->add('submit', 'submit', array('label' => 'Обфусцировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'obfuscator';
    }
}
