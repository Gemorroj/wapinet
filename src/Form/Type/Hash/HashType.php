<?php

namespace App\Form\Type\Hash;

use App\Form\Type\FileUrlType;
use App\Service\Hash;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Hash.
 */
class HashType extends AbstractType
{
    private Hash $hash;

    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $algorithms = $this->hash->getAlgorithms();
        $builder->add('algorithm', ChoiceType::class, [
            'choices' => \array_flip($algorithms),
            'label' => 'Алгоритм',
            'preferred_choices' => [
                \array_search('md5', $algorithms, true),
                \array_search('sha512', $algorithms, true),
                \array_search('crc32', $algorithms, true),
            ],
        ]);

        $builder->add('text', TextareaType::class, ['label' => 'Текст', 'required' => false]);

        $builder->add('file', FileUrlType::class, [
            'label' => false,
            'required' => false,
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Хэшировать']);
    }

    /**
     * Уникальное имя формы.
     */
    public function getBlockPrefix(): string
    {
        return 'hash_form';
    }
}
