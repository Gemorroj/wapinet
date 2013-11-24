<?php
namespace Wapinet\Bundle\Form\Type\PhpValidator;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * PhpValidator
 */
class PhpValidatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('php', 'textarea', array(
            'label' => 'PHP код',
            'required' => false,
        ));
        $builder->add('file', 'file_url', array(
            'label' => false,
            'required' => false,
        ));

        $builder->add('submit', 'submit', array('label' => 'Проверить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'php_validator';
    }
}
