<?php
namespace Wapinet\Bundle\Form\Type\Code;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\Bundle\Helper\Code;

/**
 * Code
 */
class CodeType extends AbstractType
{
    private $code;

    /**
     * CodeType constructor.
     * @param Code $code
     */
    public function __construct(Code $code)
    {
        $this->code = $code;
    }

    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('algorithm', 'choice', array(
            'choices' => $this->code->getAlgorithms(),
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
