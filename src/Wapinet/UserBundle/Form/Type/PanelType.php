<?php
namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

        $builder->add('forum', 'checkbox', array('required' => false, 'label' => 'Форум'));
        $builder->add('gist', 'checkbox', array('required' => false, 'label' => 'Блоги'));
        $builder->add('file', 'checkbox', array('required' => false, 'label' => 'Файлообменник'));
        $builder->add('archiver', 'checkbox', array('required' => false, 'label' => 'Архиватор'));
        $builder->add('proxy', 'checkbox', array('required' => false, 'label' => 'Анонимайзер'));
        $builder->add('downloads', 'checkbox', array('required' => false, 'label' => 'Развлечения'));
        $builder->add('utilities', 'checkbox', array('required' => false, 'label' => 'Утилиты'));
        $builder->add('programming', 'checkbox', array('required' => false, 'label' => 'WEB мастерская'));

        $builder->add('submit', 'submit', array('label' => 'Изменить'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wapinet\UserBundle\Entity\Panel',
        ));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'wapinet_user_panel';
    }
}
