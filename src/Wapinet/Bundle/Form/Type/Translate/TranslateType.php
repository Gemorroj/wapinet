<?php
namespace Wapinet\Bundle\Form\Type\Translate;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Translate
 *
 * @see http://api.yandex.ru/translate/doc/dg/reference/translate.xml
 */
class TranslateType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('text', 'textarea', array('label' => 'Ваш текст'));
        $builder->add('lang_from', 'choice', array(
            'label' => false,
            'choices'   => array(
                'auto' => 'Автоматически',
                'en' => 'С английского',
                'ru' => 'С русского',
                'uk' => 'С украинского',
                'be' => 'С белорусского',
                'az' => 'С азербайджанского',
                'hy' => 'С армянского',
                'el' => 'С греческого',
                'pl' => 'С польского',
                'tr' => 'С турецкого',
                'de' => 'С немецкого',
                'fr' => 'С французского',
                'es' => 'С испанского',
                'it' => 'С итальянского',
                'he' => 'С иврита',
                'zh' => 'С китайского',
            )
        ));
        $builder->add('lang_to', 'choice', array(
            'label' => false,
            'choices'   => array(
                'ru' => 'На русский',
                'en' => 'На английский',
                'uk' => 'На украинский',
                'be' => 'На белорусский',
                'az' => 'На азербайджанский',
                'hy' => 'На армянский',
                'el' => 'На греческий',
                'pl' => 'На польский',
                'tr' => 'На турецкий',
                'de' => 'На немецкий',
                'fr' => 'На французский',
                'es' => 'На испанский',
                'it' => 'На итальянский',
                'he' => 'На иврит',
                'zh' => 'На китайский',
            )
        ));

        $builder->add('submit', 'submit', array('label' => 'Перевести'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'translate';
    }
}
