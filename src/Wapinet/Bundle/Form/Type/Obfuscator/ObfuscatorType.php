<?php
namespace Wapinet\Bundle\Form\Type\Obfuscator;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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

        $builder->add('code', TextareaType::class, array('trim' => true, 'label' => 'Ваш PHP код'));
        $builder->add('remove_comments', CheckboxType::class, array('data' => true, 'required' => false, 'label' => 'Удалить комментарии'));
        $builder->add('remove_spaces', CheckboxType::class, array('data' => true, 'required' => false, 'label' => 'Удалять лишние пробелы'));

        $builder->add('submit', SubmitType::class, array('label' => 'Обфусцировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'obfuscator';
    }
}
