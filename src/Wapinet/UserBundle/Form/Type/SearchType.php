<?php
namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;

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

        $builder->add(
            'search',
            'search',
            array('attr' => array(
                'maxlength' => 5000,
                'title' => 'Слова разделенные пробелами. Работает модификатор *, кавычки и др.'),
                'required' => true,
                'label' => false,
                'constraints' => new NotBlank()
            )
        );

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
