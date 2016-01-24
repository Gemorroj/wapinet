<?php
namespace Wapinet\Bundle\Form\Type\HtmlValidator;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\UploaderBundle\Form\Type\FileUrlType;

/**
 * HtmlValidator
 */
class HtmlValidatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('html', TextareaType::class, array(
            'label' => 'HTML код',
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
        return 'html_validator';
    }
}
