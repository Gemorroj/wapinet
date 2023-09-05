<?php

namespace App\Form\Type\Gist;

use App\Entity\Gist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('subject', TextType::class, [
            'attr' => [
                'placeholder' => 'Тема',
                'maxlength' => 5000,
            ],
            'required' => true,
            'label' => false,
        ]);
        $builder->add('body', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Сообщение',
            ],
            'required' => true,
            'label' => false,
        ]);
        $builder->add('submit', SubmitType::class, ['label' => 'Добавить']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gist::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'gist_add_form';
    }
}
