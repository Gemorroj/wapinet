<?php
namespace Wapinet\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class SubscriberAdmin extends Admin
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
        $collection->remove('delete');
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
            ->add('user', null, array('label' => 'Пользователь'))
            ->add('emailComments', null, array('label' => 'Комментарии'))
            ->add('emailMessages', null, array('label' => 'Сообщения'))
            ->add('emailNews', null, array('label' => 'Новости'))
            ->add('emailFriends', null, array('label' => 'События друзей'))
        ;
    }

    /**
     * Конфигурация формы редактирования записи
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('emailComments', null, array('label' => 'Комментарии', 'required' => false))
            ->add('emailMessages', null, array('label' => 'Сообщения', 'required' => false))
            ->add('emailNews', null, array('label' => 'Новости', 'required' => false))
            ->add('emailFriends', null, array('label' => 'События друзей', 'required' => false))
        ;
    }

    /**
     * Поля, по которым производится поиск в списке записей
     *
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        //
    }

    /**
     * Конфигурация списка записей
     *
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('user', null, array('label' => 'Пользователь'))
            ->add('emailComments', null, array('label' => 'Комментарии'))
            ->add('emailMessages', null, array('label' => 'Сообщения'))
            ->add('emailNews', null, array('label' => 'Новости'))
            ->add('emailFriends', null, array('label' => 'События друзей'))
            ->add('_action', 'actions', array(
                    'label' => 'Операции',
                    'actions' => array(
                        'show' => array(),
                        'edit' => array(),
                    )
                ))
        ;
    }
}
