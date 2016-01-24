<?php
namespace Wapinet\Bundle\Form\Type\Pagerank;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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

        $builder->add('url', UrlType::class, array('label' => 'Сайт', 'data' => 'http://'));

        $builder->add('submit', SubmitType::class, array('label' => 'Анализировать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'pagerank';
    }
}
