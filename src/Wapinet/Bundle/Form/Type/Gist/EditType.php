<?php
namespace Wapinet\Bundle\Form\Type\Gist;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;

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

        $builder->add('subject', 'text', array('attr' => array('placeholder' => 'Тема', 'maxlength' => 5000), 'required' => true, 'label' => false, 'constraints' => new NotBlank()));
        $builder->add('body', 'textarea', array('attr' => array('placeholder' => 'Сообщение'), 'required' => true, 'label' => false, 'constraints' => new NotBlank()));
        $builder->add('submit', 'submit', array('label' => 'Изменить'));
    }


    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'gist_edit_form';
    }
}
