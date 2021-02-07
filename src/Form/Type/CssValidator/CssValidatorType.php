<?php

namespace App\Form\Type\CssValidator;

use App\Form\Type\FileUrlType;
use CSSValidator\Options;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * CssValidator.
 */
class CssValidatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('css', TextareaType::class, [
            'label' => 'CSS код',
            'required' => false,
        ]);
        $builder->add('file', FileUrlType::class, [
            'label' => false,
            'required' => false,
        ]);
        $builder->add('profile', ChoiceType::class, [
            'label' => 'Профиль',
            'choices' => [
                'CSS3' => Options::PROFILE_CSS3,
                'Мобильный' => Options::PROFILE_MOBILE,
                'CSS2.1' => Options::PROFILE_CSS21,
                'CSS2' => Options::PROFILE_CSS2,
                'CSS1' => Options::PROFILE_CSS1,
                'SVG' => Options::PROFILE_SVG,
                'SVG Basic' => Options::PROFILE_SVG_BASIC,
                'SVG Tiny' => Options::PROFILE_SVG_TINY,
                'телевидение ATSC' => Options::PROFILE_ATSC_TV,
                'телевидение' => Options::PROFILE_TV,
                'Без специальных настроек' => Options::PROFILE_NONE,
            ],
        ]);
        $builder->add('warning', ChoiceType::class, [
            'label' => 'Предупреждения',
            'choices' => [
                'Обычный отчет' => Options::WARNING_NORMAL,
                'Наиболее важные' => Options::WARNING_IMPORTANT,
                'Все' => Options::WARNING_ALL,
                'Без предупреждений' => Options::WARNING_NONE,
            ],
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Проверить']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'css_validator';
    }
}
