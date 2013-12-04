<?php
namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Menu
 */
class MenuType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('ch1', 'checkbox', array('required' => false, 'label' => 'Меню1'));
        $builder->add('ch2', 'checkbox', array('required' => false, 'label' => 'Меню2'));

        $builder->add('submit', 'submit', array('label' => 'Изменить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'menu';
    }
}
