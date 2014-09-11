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
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    );

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
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->add('body', null, array('label' => 'Сообщение'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('thread.subject', null, array('label' => 'Тема треда'))
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
            ->add('sender', null, array('label' => 'Автор'))
            ->add('body', null, array('label' => 'Сообщение'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
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
            ->add('sender', null, array('label' => 'Автор'))
            ->add('body', null, array('label' => 'Сообщение'))
            ->add('createdAt', 'doctrine_orm_datetime_range', array('label' => 'Дата и время'), 'sonata_type_date_range', array('widget' => 'single_text'))
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
            ->addIdentifier('body', null, array('label' => 'Сообщение'))
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
