<?php
namespace Wapinet\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class PanelAdmin extends Admin
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
            ->add('forum', null, array('label' => 'Форум'))
            ->add('guestbook', null, array('label' => 'Гостевая'))
            ->add('gist', null, array('label' => 'Блоги'))
            ->add('file', null, array('label' => 'Файлообменник'))
            ->add('archiver', null, array('label' => 'Архиватор'))
            ->add('proxy', null, array('label' => 'Анонимайзер'))
            ->add('downloads', null, array('label' => 'Развлечения'))
            ->add('utilities', null, array('label' => 'Утилиты'))
            ->add('programming', null, array('label' => 'WEB мастерская'))
        ;
    }

    /**
     * Конфигурация формы редактирования записи
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('forum', null, array('label' => 'Форум', 'required' => false))
            ->add('guestbook', null, array('label' => 'Гостевая', 'required' => false))
            ->add('gist', null, array('label' => 'Блоги', 'required' => false))
            ->add('file', null, array('label' => 'Файлообменник', 'required' => false))
            ->add('archiver', null, array('label' => 'Архиватор', 'required' => false))
            ->add('proxy', null, array('label' => 'Анонимайзер', 'required' => false))
            ->add('downloads', null, array('label' => 'Развлечения', 'required' => false))
            ->add('utilities', null, array('label' => 'Утилиты', 'required' => false))
            ->add('programming', null, array('label' => 'WEB мастерская', 'required' => false))
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
            ->add('forum', null, array('label' => 'Форум'))
            ->add('guestbook', null, array('label' => 'Гостевая'))
            ->add('gist', null, array('label' => 'Блоги'))
            ->add('file', null, array('label' => 'Файлообменник'))
            ->add('archiver', null, array('label' => 'Архиватор'))
            ->add('proxy', null, array('label' => 'Анонимайзер'))
            ->add('downloads', null, array('label' => 'Развлечения'))
            ->add('utilities', null, array('label' => 'Утилиты'))
            ->add('programming', null, array('label' => 'WEB мастерская'))
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
