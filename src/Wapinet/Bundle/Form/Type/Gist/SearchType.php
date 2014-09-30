<?php
namespace Wapinet\Bundle\Form\Type\Gist;

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

        $builder->add('search', 'search', array(
            'attr' => array('maxlength' => 5000, 'title' => 'Слова разделенные пробелами. Работает модификатор *, кавычки и др.'),
            'required' => true,
            'label' => false,
            'constraints' => new NotBlank()
        ));

        $builder->add('sort', 'choice', array(
            'choices' => array(
                'relevance' => 'релевантности',
                'date' => 'дате',
            ),
            'expanded' => true,
            'required' => true,
            //'data' => 'relevance',
            'label' => 'Сортировать по',
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
        return 'gist_search_form';
    }
}
