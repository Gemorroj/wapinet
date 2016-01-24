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
        $builder->add('warning', ChoiceType::class, array(
            'label' => 'Предупреждения',
            'choices' => array(
                '1' => 'Обычный отчет',
                '0' => 'Наиболее важные',
                '2' => 'Все',
                'no' => 'Без предупреждений',
            ),
        ));
        $builder->add('usermedium', ChoiceType::class, array(
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
