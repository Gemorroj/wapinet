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
        $builder->add('lang', 'choice', array(
            'label' => 'Языки',
            'choices'   => array(
                'en-ru' => 'С английского на русский',
                'ru-en' => 'С русского на английский',
                'ru-uk' => 'С русского на украинский',
                'uk-ru' => 'С украинского на русский',
                'be-ru' => 'С белорусского на русский',
                'ru-be' => 'С русского на белорусский',
                'az-ru' => 'С азербайджанского на русский',
                'ru-az' => 'С русского на азербайджанский',
                'hy-ru' => 'С армянского на русский',
                'ru-hy' => 'С русского на армянский',
                'el-ru' => 'С греческого на русский',
                'ru-el' => 'С русского на греческий',
                'pl-ru' => 'С польского на русский',
                'ru-pl' => 'С русского на польский',
                'tr-ru' => 'С турецкого на русский',
                'ru-tr' => 'С русского на турецкий',
                'de-ru' => 'С немецкого на русский',
                'ru-de' => 'С русского на немецкий',
                'fr-ru' => 'С французского на русский',
                'ru-fr' => 'С русского на французкий',
                'es-ru' => 'С испанского на русский',
                'ru-es' => 'С русского на испанский',
                'it-ru' => 'С итальянского на русский',
                'ru-it' => 'С русского на итальянский',
            )));

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
