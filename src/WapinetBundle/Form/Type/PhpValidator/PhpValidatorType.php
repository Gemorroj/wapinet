<?php
namespace WapinetBundle\Form\Type\PhpValidator;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use WapinetUploaderBundle\Form\Type\FileUrlType;

/**
 * PhpValidator
 */
class PhpValidatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('php', TextareaType::class, array(
            'label' => 'PHP код (' . \phpversion() . ')',
            'required' => false,
        ));
        $builder->add('file', FileUrlType::class, array(
            'label' => false,
            'required' => false,
        ));

        $builder->add('submit', SubmitType::class, array('label' => 'Проверить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'php_validator';
    }
}