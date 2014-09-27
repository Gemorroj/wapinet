<?php
namespace Wapinet\Bundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Wapinet\Bundle\Entity\File;
use Wapinet\Bundle\Helper\File as FileHelper;

class FileAdmin extends Admin
{
    /**
     * @var FileHelper
     */
    protected $fileHelper;

    protected $baseRouteName = 'sonata_file';
    protected $baseRoutePattern = 'file';
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    );

    public function setFileHelper(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

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
            ->add('originalFileName', null, array('label' => 'Имя'))
            ->add('createdAt', null, array('label' => 'Дата и время создания'))
            ->add('updatedAt', null, array('label' => 'Дата и время обновления'))
            ->add('lastViewAt', null, array('label' => 'Дата и время последнего просмотра'))
            ->add('user', null, array('label' => 'Пользователь'))
            ->add('mimeType', null, array('label' => 'MIME тип'))
            ->add('description', null, array('label' => 'Описание'))
            ->add('countViews', null, array('label' => 'Просмотров'))
            ->add('password', 'boolean', array('label' => 'Пароль'))
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
            ->add('originalFileName', null, array('label' => 'Имя'))
            ->add('mimeType', null, array('label' => 'MIME тип'))
            ->add('description', null, array('label' => 'Описание'))
            ->add('countViews', null, array('label' => 'Просмотров'))
        ;
    }


    /**
     * @param File $file
     */
    public function postRemove($file)
    {
        $this->fileHelper->cleanupFile($file);
    }

    /**
     * Поля, по которым производится поиск в списке записей
     *
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('user', null, array('label' => 'Пользователь'))
            ->add('originalFileName', null, array('label' => 'Имя'))
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
            ->addIdentifier('originalFileName', null, array('label' => 'Имя'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('user', null, array('label' => 'Пользователь'))
            ->add('mimeType', null, array('label' => 'MIME тип'))
            ->add('description', null, array('label' => 'Описание'))
            ->add('countViews', null, array('label' => 'Просмотров'))
            ->add('password', 'boolean', array('label' => 'Пароль'))
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
