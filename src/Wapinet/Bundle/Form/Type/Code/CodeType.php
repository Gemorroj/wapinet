<?php
namespace Wapinet\Bundle\Form\Type\Code;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Code
 */
class CodeType extends AbstractType
{
    protected $algorithms = array();

    /**
     * @param array $algorithms
     */
    public function __construct(array $algorithms)
    {
        $this->algorithms = $algorithms;
    }

    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('algorithm', 'choice', array(
            'choices' => $this->algorithms,
            'label' => 'Алгоритм',
        ));

        $builder->add('text', 'textarea', array('label' => 'Текст', 'required' => false));

        //$builder->add('file', 'file_url', array(
        //    'label' => false,
        //    'required' => false,
        //));

        $builder->add('submit', 'submit', array('label' => 'Конвертировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'code_form';
    }
}
