<?php
namespace Wapinet\Bundle\Form\Type\Hash;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Hash
 */
class HashType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $algorithms = \hash_algos();
        $builder->add('algorithm', 'choice', array(
            'choices' => $algorithms,
            'label' => 'Алгоритм',
            'preferred_choices' => array(array_search('md5', $algorithms), array_search('sha512', $algorithms), array_search('crc32', $algorithms)),
        ));

        $builder->add('text', 'textarea', array('label' => 'Текст'));

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
