<?php
namespace Wapinet\Bundle\Form\Type\Whois;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Whois
 */
class WhoisType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('query', 'text', array('label' => 'Домен или IP'));

        $builder->add('submit', 'submit', array('label' => 'Смотреть'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'whois';
    }
}
