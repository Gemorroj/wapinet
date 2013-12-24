<?php
namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\UserBundle\Entity\User;

/**
 * Search
 */
class SearchType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('search', 'search', array('max_length' => 5000, 'required' => false, 'label' => 'Кого ищем?'));

        $builder->add('use_info', 'checkbox', array('required' => false, 'label' => 'Искать в дополнительной информации', 'data' => true));
        $builder->add('only_online', 'checkbox', array('required' => false, 'label' => 'Только онлайн', 'data' => true));
        $builder->add('sex', 'choice', array('required' => false, 'multiple' => true, 'empty_value' => 'Все', 'label' => 'Пол', 'choices' => User::getSexChoices()));
        $builder->add('created_after', 'date', array('widget' => 'single_text', 'label' => 'Зарегистрирован после', 'required' => false));
        $builder->add('created_before', 'date', array('widget' => 'single_text', 'label' => 'Зарегистрирован до', 'required' => false));

        $builder->add('submit', 'submit', array('label' => 'Искать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'users_search_form';
    }
}
