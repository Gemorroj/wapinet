<?php

namespace Wapinet\UserBundle\Form\Type;

use FOS\UserBundle\Form\Type\ProfileFormType as FOSProfileFormType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileFormType extends FOSProfileFormType
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
