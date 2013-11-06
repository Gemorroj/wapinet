<?php

namespace Wapinet\NewsBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class NewsAdmin extends Admin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('subject', null, array('label' => 'Заголовок'))
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->addIdentifier('subject', null, array('label' => 'Заголовок'))
            ->add('createdAt', null, array('label' => 'Создано'))
            ->add('updatedAt', null, array('label' => 'Обновлено'))
            ->add('_action', 'actions', array(
                'label' => 'Операции',
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('subject', null, array('label' => 'Заголовок'))
            ->add('body', null, array('label' => 'Новость'))
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->add('subject', null, array('label' => 'Заголовок'))
            ->add('body', null, array('label' => 'Новость'))
            ->add('createdAt', null, array('label' => 'Создано'))
            ->add('updatedAt', null, array('label' => 'Обновлено'))
        ;
    }
}
