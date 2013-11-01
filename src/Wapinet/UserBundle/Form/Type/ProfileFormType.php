<?php

namespace Wapinet\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;

class ProfileFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // add your custom field
        $builder->add('avatar', 'file', array('label' => 'form.avatar', 'translation_domain' => 'FOSUserBundle'));
    }

    public function getName()
    {
        return 'wapinet_user_profile';
    }
}
