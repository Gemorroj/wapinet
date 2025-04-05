<?php

namespace App\Form\Type\Whois;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WhoisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('query', TextType::class, ['label' => 'Домен ASN или IP']);
        $builder->add('type', ChoiceType::class, [
            'choices' => [
                'WHOIS' => 'WHOIS',
                'RDAP' => 'RDAP',
            ],
            'label' => 'Проткол',
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Смотреть']);
    }

    public function getBlockPrefix(): string
    {
        return 'whois';
    }
}
