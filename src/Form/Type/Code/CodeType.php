<?php

namespace App\Form\Type\Code;

use App\Service\Code;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Code.
 */
class CodeType extends AbstractType
{
    private Code $code;

    public function __construct(Code $code)
    {
        $this->code = $code;
    }

    /**
     * @var FormBuilderInterface
     * @var array
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
     * Уникальное имя формы.
     */
    public function getBlockPrefix(): string
    {
        return 'code_form';
    }
}
