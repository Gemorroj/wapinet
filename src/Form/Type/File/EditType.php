<?php

namespace App\Form\Type\File;

use App\Entity\File;
use App\Form\Type\FileUrlType;
use App\Form\Type\TagsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as CorePasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['required' => false, 'label' => false]);
        $builder->add('description', TextareaType::class, [
            'attr' => [
                'maxlength' => 5000,
            ],
            'required' => true,
            'label' => 'Описание',
        ]);

        // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/autocomplete/autocomplete-remote.html
        // тэги
        $builder->add('tags', TagsType::class, ['required' => false, 'label' => 'Тэги через запятую']);

        $builder->add('plainPassword', CorePasswordType::class, [
            'required' => false,
            'label' => 'Пароль',
            'attr' => [
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Загрузить']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => File::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'file_edit_form';
    }
}
