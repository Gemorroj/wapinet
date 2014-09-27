<?php
namespace Wapinet\CommentBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;

class CommentAdmin extends Admin
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
            ->add('body', null, array('label' => 'Комментарий'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('thread.permalink', 'url', array('label' => 'Тред'))
            ->add('author', null, array('label' => 'Автор'))
            ->add('score', null, array('label' => 'Понравилось'))
            ->add('ip', null, array('label' => 'IP'))
            ->add('browser', null, array('label' => 'Браузер'))
        ;
    }

    /**
     * Конфигурация формы редактирования записи
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('author', null, array('label' => 'Автор'))
            ->add('body', null, array('label' => 'Комментарий'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('score', null, array('label' => 'Понравилось'))
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
            ->add('author', null, array('label' => 'Автор'))
            ->add('body', null, array('label' => 'Комментарий'))
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
            ->addIdentifier('body', null, array('label' => 'Комментарий'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('author', null, array('label' => 'Автор'))
            ->add('score', null, array('label' => 'Понравилось'))
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
