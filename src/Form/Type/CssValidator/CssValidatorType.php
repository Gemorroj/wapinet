<?php

namespace App\Form\Type\CssValidator;

use App\Form\Type\FileUrlType;
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
                'CSS3' => 'css3',
                'Мобильный' => 'mobile',
                'CSS2.1' => 'css21',
                'CSS2' => 'css2',
                'CSS1' => 'css1',
                'SVG' => 'svg',
                'SVG Basic' => 'svgbasic',
                'SVG tiny' => 'svgtiny',
                'телевидение ATSC' => 'atsc-tv',
                'телевидение' => 'tv',
                'Без специальных настроек' => 'none',
            ],
        ]);
        $builder->add('warning', ChoiceType::class, [
            'label' => 'Предупреждения',
            'choices' => [
                'Обычный отчет' => '1',
                'Наиболее важные' => '0',
                'Все' => '2',
                'Без предупреждений' => 'no',
            ],
        ]);
        $builder->add('usermedium', ChoiceType::class, [
            'label' => 'Среда',
            'choices' => [
                'Все' => 'all',
                'аудио (aural)' => 'aural',
                'терминал Брайля (braille)' => 'braille',
                'постраничный принтер Брайля (embossed)' => 'embossed',
                'портативное устройство (handheld)' => 'handheld',
                'печатная продукция (print)' => 'print',
                'проектор (projection)' => 'projection',
                'дисплей (screen)' => 'screen',
                'телетайп (tty)' => 'tty',
                'телевизор (tv)' => 'tv',
                'презентация (presentation)' => 'presentation',
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
