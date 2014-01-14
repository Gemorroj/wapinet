<?php
namespace Wapinet\Bundle\Form\Type\Gist;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;

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

        $builder->add('subject', 'text', array('attr' => array('placeholder' => 'Тема'), 'max_length' => 5000, 'required' => true, 'label' => false, 'constraints' => new NotBlank()));
        $builder->add('body', 'textarea', array('attr' => array('placeholder' => 'Сообщение'), 'required' => true, 'label' => false, 'constraints' => new NotBlank()));
        $builder->add('submit', 'submit', array('label' => 'Добавить'));
    }


    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'add_form';
    }
}
