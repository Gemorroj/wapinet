<?php
namespace WapinetBundle\Form\Type\Rename;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use WapinetBundle\Form\Type\FileUrlType;

/**
 * Rename
 */
class RenameType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, array('required' => true, 'label' => false));
        $builder->add('name', TextType::class, array('label' => 'Новое название'));

        $builder->add('submit', SubmitType::class, array('label' => 'Переименовать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'rename';
    }
}
