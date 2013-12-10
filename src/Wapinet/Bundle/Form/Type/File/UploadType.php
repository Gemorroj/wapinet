<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
        $builder->add('description', 'textarea', array('max_length' => 5000, 'required' => true, 'label' => 'Описание'));
        //$builder->add('categories', 'choice', array('required' => false, 'label' => 'Категории', 'multiple' => true, 'choices' => array('sdfg','asdfasd'), 'attr' => array('data-icon' => 'grid', 'data-native-menu' => 'false')));
        $builder->add('password', 'password', array('required' => false, 'label' => 'Пароль', 'attr' => array('autocomplete' => 'off')));

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
