<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Search
 */
class SearchType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('search', 'search', array('max_length' => 5000, 'required' => false, 'label' => 'Что ищем?'));

        $builder->add('use_description', 'checkbox', array('required' => false, 'label' => 'Искать в описании', 'data' => true));
        $builder->add('categories', 'choice', array('required' => false, 'multiple' => true, 'empty_value' => 'Все', 'label' => 'Тип', 'choices' => array(
            'video' => 'Видео',
            'audio' => 'Аудио',
            'image' => 'Картинки',
            'text' => 'Текстовые файлы',
            'office' => 'Офисные документы',
            'archive' => 'Архивы',
            'android' => 'Приложения Android',
            'java' => 'Приложения Java',
        )));
        $builder->add('created_after', 'date', array('widget' => 'single_text', 'label' => 'Загружены после', 'required' => false));
        $builder->add('created_before', 'date', array('widget' => 'single_text', 'label' => 'Загружены до', 'required' => false));

        $builder->add('submit', 'submit', array('label' => 'Искать'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'search_form';
    }
}
