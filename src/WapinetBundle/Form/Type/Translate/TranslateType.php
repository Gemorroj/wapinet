<?php
namespace WapinetBundle\Form\Type\Translate;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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

        $builder->add('text', TextareaType::class, array('label' => 'Ваш текст'));
        $builder->add('lang_from', ChoiceType::class, array(
            'label' => false,
            'choices' => array(
                'Автоматически' => 'auto',
                'С английского' => 'en',
                'С русского' => 'ru',
                'С украинского' => 'uk',
                'С белорусского' => 'be',
                'С азербайджанского' => 'az',
                'С армянского' => 'hy',
                'С греческого' => 'el',
                'С польского' => 'pl',
                'С турецкого' => 'tr',
                'С немецкого' => 'de',
                'С французского' => 'fr',
                'С испанского' => 'es',
                'С итальянского' => 'it',
                'С иврита' => 'he',
                'С китайского' => 'zh',
            )
        ));
        $builder->add('lang_to', ChoiceType::class, array(
            'label' => false,
            'choices' => array(
                'На русский' => 'ru',
                'На английский' => 'en',
                'На украинский' => 'uk',
                'На белорусский' => 'be',
                'На азербайджанский' => 'az',
                'На армянский' => 'hy',
                'На греческий' => 'el',
                'На польский' => 'pl',
                'На турецкий' => 'tr',
                'На немецкий' => 'de',
                'На французский' => 'fr',
                'На испанский' => 'es',
                'На итальянский' => 'it',
                'На иврит' => 'he',
                'На китайский' => 'zh',
            )
        ));

        $builder->add('submit', SubmitType::class, array('label' => 'Перевести'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'translate';
    }
}
