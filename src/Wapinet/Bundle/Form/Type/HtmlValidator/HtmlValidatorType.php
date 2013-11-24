<?php
namespace Wapinet\Bundle\Form\Type\HtmlValidator;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * HtmlValidator
 */
class HtmlValidatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('html', 'textarea', array(
            'label' => 'HTML код',
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
        return 'html_validator';
    }
}
