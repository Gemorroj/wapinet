<?php

namespace App\Form\Type\Whois;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Whois.
 */
class WhoisType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('query', TextType::class, ['label' => 'Домен или IP']);

        $builder->add('submit', SubmitType::class, ['label' => 'Смотреть']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'whois';
    }
}
