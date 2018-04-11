<?php
namespace WapinetBundle\Form\Type\Code;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use WapinetBundle\Helper\Code;

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

        $builder->add('algorithm', ChoiceType::class, [
            'choices' => \array_flip($this->code->getAlgorithms()),
            'label' => 'Алгоритм',
        ]);

        $builder->add('text', TextareaType::class, ['label' => 'Текст', 'required' => false]);

        //$builder->add('file', FileUrlType::class, [
        //    'label' => false,
        //    'required' => false,
        //]);

        $builder->add('submit', SubmitType::class, ['label' => 'Конвертировать']);
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
