<?php
namespace Wapinet\Bundle\Form\Type\CssValidator;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\UploaderBundle\Form\Type\FileUrlType;

/**
 * CssValidator
 */
class CssValidatorType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('css', TextareaType::class, array(
            'label' => 'CSS код',
            'required' => false,
        ));
        $builder->add('file', FileUrlType::class, array(
            'label' => false,
            'required' => false,
        ));
        $builder->add('profile', ChoiceType::class, array(
            'label' => 'Профиль',
            'choices' => array(
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
            ),
        ));
        $builder->add('warning', ChoiceType::class, array(
            'label' => 'Предупреждения',
            'choices' => array(
                'Обычный отчет' => '1',
                'Наиболее важные' => '0',
                'Все' => '2',
                'Без предупреждений' => 'no',
            ),
        ));
        $builder->add('usermedium', ChoiceType::class, array(
            'label' => 'Среда',
            'choices' => array(
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
            ),
        ));

        $builder->add('submit', SubmitType::class, array('label' => 'Проверить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'css_validator';
    }
}
