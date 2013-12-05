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
    protected $userManager;

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
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->add('forum', null, array('label' => 'Форум'))
            ->add('files', null, array('label' => 'Файлообменник'))
            ->add('archiver', null, array('label' => 'Архиватор'))
            ->add('proxy', null, array('label' => 'Анонимайзер'))
            ->add('downloads', null, array('label' => 'Загрузки, развлечения'))
            ->add('utilities', null, array('label' => 'Полезные WEB приложения'))
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
            ->add('files', null, array('label' => 'Файлообменник', 'required' => false))
            ->add('archiver', null, array('label' => 'Архиватор', 'required' => false))
            ->add('proxy', null, array('label' => 'Анонимайзер', 'required' => false))
            ->add('downloads', null, array('label' => 'Загрузки, развлечения', 'required' => false))
            ->add('utilities', null, array('label' => 'Полезные WEB приложения', 'required' => false))
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
            ->addIdentifier('id', null, array('label' => 'Идентификатор'))
            ->add('forum', null, array('label' => 'Форум'))
            ->add('files', null, array('label' => 'Файлообменник'))
            ->add('archiver', null, array('label' => 'Архиватор'))
            ->add('proxy', null, array('label' => 'Анонимайзер'))
            ->add('downloads', null, array('label' => 'Загрузки, развлечения'))
            ->add('utilities', null, array('label' => 'Полезные WEB приложения'))
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
