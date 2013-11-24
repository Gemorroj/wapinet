<?php
namespace Wapinet\Bundle\Form\Type\Rest;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Rest
 */
class RestType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('type', 'choice', array(
            'choices' => array(
                'GET' => 'GET',
                'POST' => 'POST',
                'PUT' => 'PUT',
                'DELETE' => 'DELETE',
                'PATCH' => 'PATCH',
                'HEAD' => 'HEAD',
                'OPTIONS' => 'OPTIONS',
            ),
            'label' => 'Тип',
        ));
        $builder->add('url', 'url', array(
            'label' => 'Путь'
        ));
        $builder->add('header', 'textarea', array(
            'data' => 'Accept: */*' . "\r\n" . 'Cache-Control: no-cache' . "\r\n" . 'User-Agent: Wapinet REST Client',
            'required' => false,
            'label' => 'Заголовки'
        ));
        $builder->add('body', 'textarea', array(
            'required' => false,
            'label' => 'Тело'
        ));

        $builder->add('submit', 'submit', array('label' => 'Выполнить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'rest';
    }
}
