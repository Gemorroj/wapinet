<?php
namespace Wapinet\Bundle\Form\Type\Unicode;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Unicode
 */
class UnicodeType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('text', TextareaType::class, array('label' => 'Ваш текст'));
        $builder->add('latin', CheckboxType::class, array('required' => false, 'label' => 'Заменять на латиницу'));
        $builder->add('zerofill', CheckboxType::class, array('required' => false, 'label' => 'Удалять лишние нули'));
        $builder->add('html', CheckboxType::class, array('required' => false, 'label' => 'Преобразовывать специальные символы в HTML-сущности'));

        $builder->add('submit', SubmitType::class, array('label' => 'Перекодировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'unicode';
    }
}
