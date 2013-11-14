<?php
namespace Wapinet\Bundle\Form\Type\Pagerank;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Pagerank
 */
class PagerankType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('url', 'url', array('label' => 'Сайт', 'data' => 'http://'));

        $builder->add('submit', 'submit', array('label' => 'Анализировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'pagerank';
    }
}
