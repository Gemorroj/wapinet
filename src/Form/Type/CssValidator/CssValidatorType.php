<?php

namespace App\Form\Type\CssValidator;

use App\Form\Type\FileUrlType;
use CSSValidator\Options\ProfileType;
use CSSValidator\Options\WarningType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class CssValidatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'CSS3' => ProfileType::Css3->value,
                'Мобильный' => ProfileType::Mobile->value,
                'CSS2.1' => ProfileType::Css21->value,
                'CSS2' => ProfileType::Css2->value,
                'CSS1' => ProfileType::Css1->value,
                'SVG' => ProfileType::Svg->value,
                'SVG Basic' => ProfileType::SvgBasic->value,
                'SVG Tiny' => ProfileType::SvgTiny->value,
                'телевидение ATSC' => ProfileType::AtscTv->value,
                'телевидение' => ProfileType::Tv->value,
                'Без специальных настроек' => ProfileType::None->value,
            ],
        ]);
        $builder->add('warning', ChoiceType::class, [
            'label' => 'Предупреждения',
            'choices' => [
                'Обычный отчет' => WarningType::Normal->value,
                'Наиболее важные' => WarningType::Important->value,
                'Все' => WarningType::All->value,
                'Без предупреждений' => WarningType::None->value,
            ],
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Проверить']);
    }

    public function getBlockPrefix(): string
    {
        return 'css_validator';
    }
}
