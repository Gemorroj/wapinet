<?php

namespace App\Form\Type\User;

use App\Entity\UserPanel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PanelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('forum', CheckboxType::class, ['required' => false, 'label' => 'Форум']);
        $builder->add('guestbook', CheckboxType::class, ['required' => false, 'label' => 'Гостевая']);
        $builder->add('gist', CheckboxType::class, ['required' => false, 'label' => 'Блоги']);
        $builder->add('file', CheckboxType::class, ['required' => false, 'label' => 'Файлообменник']);
        $builder->add('archiver', CheckboxType::class, ['required' => false, 'label' => 'Архиватор']);
        $builder->add('downloads', CheckboxType::class, ['required' => false, 'label' => 'Развлечения']);
        $builder->add('utilities', CheckboxType::class, ['required' => false, 'label' => 'Утилиты']);
        $builder->add('programming', CheckboxType::class, ['required' => false, 'label' => 'WEB мастерская']);

        $builder->add('submit', SubmitType::class, ['label' => 'Изменить']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPanel::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wapinet_user_panel';
    }
}
