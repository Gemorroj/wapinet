<?php
namespace Wapinet\MessageBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;

class MessageAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('create');
    }

    /**
     * Конфигурация отображения записи
     *
     * @param ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->add('body', null, array('label' => 'Сообщение'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('sender', null, array('label' => 'Автор'))
        ;
    }

    /**
     * Конфигурация формы редактирования записи
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('body', null, array('label' => 'Сообщение'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('sender', null, array('label' => 'Автор'))
        ;
    }

    /**
     * Поля, по которым производится поиск в списке записей
     *
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('body', null, array('label' => 'Сообщение'))
            ->add('sender', null, array('label' => 'Автор'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
        ;
    }

    /**
     * Конфигурация списка записей
     *
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null, array('label' => 'Идентификатор'))
            ->add('body', null, array('label' => 'Сообщение'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('sender', null, array('label' => 'Автор'))
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
}
