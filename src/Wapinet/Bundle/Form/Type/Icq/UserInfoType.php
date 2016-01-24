<?php
namespace Wapinet\Bundle\Form\Type\Icq;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Icq user info
 */
class UserInfoType extends AbstractType
{
    /**
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('uin', NumberType::class, array('label' => 'UIN', 'required' => true));
        $builder->add('submit', SubmitType::class, array('label' => 'Смотреть'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'icq_user_info';
    }
}
