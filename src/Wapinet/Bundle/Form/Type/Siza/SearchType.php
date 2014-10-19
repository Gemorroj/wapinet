<?php
namespace Wapinet\Bundle\Form\Type\Siza;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\Length;
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

        $builder->add('search', 'search', array(
            'constraints' => array(
                new NotBlank(),
                new Length(array('min' => 3)),
            ),
            'required' => true,
            'label' => 'Поиск',
        ));

        $builder->add('submit', 'submit', array('label' => 'Искать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'siza_search_form';
    }
}
