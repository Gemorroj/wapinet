<?php
namespace Wapinet\Bundle\Form\Type\Hash;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Hash
 */
class HashType extends AbstractType
{
    protected $algorithms = array();

    /**
     * @param array $algorithms
     */
    public function __construct(array $algorithms)
    {
        $this->algorithms = $algorithms;
    }

    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('algorithm', 'choice', array(
            'choices' => $this->algorithms,
            'label' => 'Алгоритм',
            'preferred_choices' => array(
                array_search('md5', $this->algorithms, true),
                array_search('sha512', $this->algorithms, true),
                array_search('crc32', $this->algorithms, true)
            ),
        ));

        $builder->add('text', 'textarea', array('label' => 'Текст', 'required' => false));

        $builder->add('file', 'file_url', array(
            'label' => false,
            'required' => false,
        ));

        $builder->add('submit', 'submit', array('label' => 'Хэшировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'hash_form';
    }
}
