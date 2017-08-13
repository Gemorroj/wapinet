<?php
namespace WapinetUserBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WapinetUserBundle\Entity\Panel;

/**
 * Panel
 */
class PanelType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('forum', CheckboxType::class, array('required' => false, 'label' => 'Форум'));
        $builder->add('guestbook', CheckboxType::class, array('required' => false, 'label' => 'Гостевая'));
        $builder->add('gist', CheckboxType::class, array('required' => false, 'label' => 'Блоги'));
        $builder->add('file', CheckboxType::class, array('required' => false, 'label' => 'Файлообменник'));
        $builder->add('archiver', CheckboxType::class, array('required' => false, 'label' => 'Архиватор'));
        $builder->add('proxy', CheckboxType::class, array('required' => false, 'label' => 'Анонимайзер'));
        $builder->add('downloads', CheckboxType::class, array('required' => false, 'label' => 'Развлечения'));
        $builder->add('utilities', CheckboxType::class, array('required' => false, 'label' => 'Утилиты'));
        $builder->add('programming', CheckboxType::class, array('required' => false, 'label' => 'WEB мастерская'));

        $builder->add('submit', SubmitType::class, array('label' => 'Изменить'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Panel::class,
        ));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wapinet_user_panel';
    }
}
