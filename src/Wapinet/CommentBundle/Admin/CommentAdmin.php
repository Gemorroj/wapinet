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
            ->add('body', null, array('label' => 'Комментарий'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('author', null, array('label' => 'Автор'))
            ->add('score', null, array('label' => 'Понравилось'))
        ;
    }

    /**
     * Конфигурация формы редактирования записи
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('body', null, array('label' => 'Комментарий'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('author', null, array('label' => 'Автор'))
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
            ->add('body', null, array('label' => 'Комментарий'))
            ->add('author', null, array('label' => 'Автор'))
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
            ->add('body', null, array('label' => 'Комментарий'))
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