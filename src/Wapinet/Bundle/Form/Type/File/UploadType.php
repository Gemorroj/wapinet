<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Upload
 */
class UploadType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('required' => true, 'label' => false));
        $builder->add('description', 'textarea', array('max_length' => 5000, 'required' => true, 'label' => 'Описание', 'constraints' => new NotBlank()));
        $builder->add('password', 'password', array('required' => false, 'label' => 'Пароль', 'attr' => array('autocomplete' => 'off'), 'constraints' => new NotBlank()));

        $builder->add('submit', 'submit', array('label' => 'Загрузить'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wapinet\Bundle\Entity\File',
        ));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'upload_form';
    }
}
