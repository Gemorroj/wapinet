<?php
namespace Wapinet\Bundle\Form\Type\Weather;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * City
 */
class CityType extends AbstractType
{
    protected $countries = array();

    public function __construct(array $countries)
    {
        $this->countries = $countries;
    }

    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('city', 'choice', array('label' => 'Город', 'choices' => $this->countries));

        $builder->add('submit', 'submit', array('label' => 'Дальше'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'weather_city';
    }
}
