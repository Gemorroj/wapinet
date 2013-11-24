<?php
namespace Wapinet\Bundle\Form\Type\CssValidator;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

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

        $builder->add('css', 'textarea', array(
            'label' => 'CSS код',
        ));
        $builder->add('profile', 'choice', array(
            'label' => 'Профиль',
            'choices' => array(
                'css3' => 'CSS3',
                'mobile' => 'Мобильный',
                'css21' => 'CSS2.1',
                'css2' => 'CSS2',
                'css1' => 'CSS1',
                'svg' => 'SVG',
                'svgbasic' => 'SVG Basic',
                'svgtiny' => 'SVG tiny',
                'atsc-tv' => 'телевидение ATSC',
                'tv' => 'телевидение',
                'none' => 'Без специальных настроек',
            ),
        ));
        $builder->add('warning', 'choice', array(
            'label' => 'Предупреждения',
            'choices' => array(
                '1' => 'Обычный отчет',
                '0' => 'Наиболее важные',
                '2' => 'Все',
                'no' => 'Без предупреждений',
            ),
        ));
        $builder->add('usermedium', 'choice', array(
            'label' => 'Среда',
            'choices' => array(
                'all' => 'Все',
                'aural' => 'аудио (aural)',
                'braille' => 'терминал Брайля (braille)',
                'embossed' => 'постраничный принтер Брайля (embossed)',
                'handheld' => 'портативное устройство (handheld)',
                'print' => 'печатная продукция (print)',
                'projection' => 'проектор (projection)',
                'screen' => 'дисплей (screen)',
                'tty' => 'телетайп (tty)',
                'tv' => 'телевизор (tv)',
                'presentation' => 'презентация (presentation)',
            ),
        ));

        $builder->add('submit', 'submit', array('label' => 'Проверить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'css_validator';
    }
}
