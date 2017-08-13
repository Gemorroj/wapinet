<?php
namespace WapinetBundle\Form\Type\Archiver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use WapinetUploaderBundle\Form\Type\FileUrlType;

/**
 * Archiver Add
 */
class AddType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, array('required' => true, 'label' => false));

        $builder->add('submit', SubmitType::class, array('label' => 'Добавить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'archiver_add';
    }
}
