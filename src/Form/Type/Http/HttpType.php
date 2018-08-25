<?php

namespace App\Form\Type\Http;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Http.
 */
class HttpType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('type', ChoiceType::class, [
            'choices' => [
                'GET' => 'GET',
                'POST' => 'POST',
                'PUT' => 'PUT',
                'DELETE' => 'DELETE',
                'PATCH' => 'PATCH',
                'HEAD' => 'HEAD',
                'OPTIONS' => 'OPTIONS',
                'TRACE' => 'TRACE',
                'CONNECT' => 'CONNECT',
            ],
            'label' => 'Тип',
        ]);
        $builder->add('url', UrlType::class, [
            'label' => 'Путь',
        ]);
        $builder->add('header', TextareaType::class, [
            'data' => 'Accept: */*'."\r\n".'Cache-Control: no-cache'."\r\n".'User-Agent: Wapinet HTTP Client',
            'required' => false,
            'label' => 'Заголовки',
        ]);
        $builder->add('body', TextareaType::class, [
            'required' => false,
            'label' => 'Тело',
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Выполнить']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'http';
    }
}
