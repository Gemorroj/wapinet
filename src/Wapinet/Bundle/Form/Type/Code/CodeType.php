<?php
namespace Wapinet\Bundle\Form\Type\Code;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\Bundle\Helper\Code;
use Wapinet\UploaderBundle\Form\Type\FileUrlType;

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

        $builder->add('algorithm', ChoiceType::class, array(
            'choices' => $this->code->getAlgorithms(),
            'label' => 'Алгоритм',
        ));

        $builder->add('text', TextareaType::class, array('label' => 'Текст', 'required' => false));

        //$builder->add('file', FileUrlType::class, array(
        //    'label' => false,
        //    'required' => false,
        //));

        $builder->add('submit', SubmitType::class, array('label' => 'Конвертировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'code_form';
    }
}
