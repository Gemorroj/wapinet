<?php

namespace App\Form\Type\File;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType as CoreSearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Search.
 */
class SearchType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('search', CoreSearchType::class, [
            'attr' => [
                'maxlength' => 5000,
                'minlength' => 3,
            ],
            'required' => true,
            'label' => false,
            'constraints' => [new Length(3, 5000)],
        ]);

        $builder->add('sort', ChoiceType::class, [
            'choices' => [
                'релевантности' => 'relevance',
                'дате' => 'date',
            ],
            'expanded' => true,
            'required' => true,
            //'data' => 'relevance',
            'label' => 'Сортировать по',
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Искать']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'file_search_form';
    }
}
