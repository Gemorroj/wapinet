<?php
namespace Wapinet\Bundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;

class TagAdmin extends Admin
{
    protected $baseRouteName = 'sonata_tag';
    protected $baseRoutePattern = 'tag';
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
            ->add('name', null, array('label' => 'Имя'))
            ->add('createdAt', null, array('label' => 'Дата и время создания'))
            ->add('updatedAt', null, array('label' => 'Дата и время обновления'))
            ->add('count', null, array('label' => 'Использовано раз'))
            ->add('files', null, array('label' => 'Файлы'))
        ;
    }

    /**
     * Конфигурация формы редактирования записи
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', null, array('label' => 'Имя'))
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
            ->add('name', null, array('label' => 'Имя'))
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
            ->addIdentifier('name', null, array('label' => 'Имя'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('count', null, array('label' => 'Использовано раз'))
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
