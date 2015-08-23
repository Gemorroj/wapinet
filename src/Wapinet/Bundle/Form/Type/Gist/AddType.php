<?php
namespace Wapinet\Bundle\Form\Type\Gist;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Add
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

        $builder->add('subject', 'text', array('attr' => array('placeholder' => 'Тема'), 'required' => true, 'label' => false));
        $builder->add('body', 'textarea', array('attr' => array('placeholder' => 'Сообщение'), 'required' => true, 'label' => false));
        $builder->add('submit', 'submit', array('label' => 'Добавить'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wapinet\Bundle\Entity\Gist',
        ));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'gist_add_form';
    }
}
