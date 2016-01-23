<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Edit
 */
class EditType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('required' => false, 'label' => false));
        $builder->add('description', 'textarea', array('required' => true, 'label' => 'Описание'));

        // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/autocomplete/autocomplete-remote.html
        // тэги
        $builder->add('tags', 'tags', array('required' => false, 'label' => 'Тэги через запятую'));


        $builder->add('plainPassword', 'password', array('required' => false, 'label' => 'Пароль', 'attr' => array('autocomplete' => 'off')));

        $builder->add('submit', 'submit', array('label' => 'Загрузить'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
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
        return 'file_edit_form';
    }
}
