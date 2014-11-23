<?php
namespace Wapinet\Bundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Wapinet\Bundle\Entity\Gist;
use Wapinet\CommentBundle\Helper\Manager;

class GistAdmin extends Admin
{
    protected $baseRouteName = 'sonata_gist';
    protected $baseRoutePattern = 'gist';
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    );
    /**
     * @var Manager
     */
    protected $commentHelper;

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('create');
    }


    /**
     * @param Manager $commentHelper
     */
    public function setCommentHelper(Manager $commentHelper)
    {
        $this->commentHelper = $commentHelper;
    }


    /**
     * @param Gist $gist
     */
    public function preRemove($gist)
    {
        if (!$gist instanceof Gist) {
            throw new \InvalidArgumentException('Некорректный тип данных');
        }

        $this->commentHelper->removeThread('gist-' . $gist->getId());
    }

    /**
     * Работает только для действий со множеством элементов
     * {@inheritdoc}
     * https://github.com/sonata-project/SonataAdminBundle/pull/1318
     */
    public function preBatchAction($actionName, ProxyQueryInterface $query, array & $idx, $allElements)
    {
        if (true === $allElements) {
            throw new \InvalidArgumentException('Удаление всех блогов не поддерживается ');
        }

        if ('delete' === $actionName) {
            foreach ($idx as $id) {
                $file = $this->getObject($id);
                $this->preRemove($file);
            }
        }
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
            ->add('createdAt', null, array('label' => 'Дата и время создания'))
            ->add('updatedAt', null, array('label' => 'Дата и время обновления'))
            ->add('user', null, array('label' => 'Пользователь'))
            ->add('subject', null, array('label' => 'Тема'))
            ->add('body', null, array('label' => 'Сообщение'))
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
            ->add('subject', null, array('label' => 'Тема'))
            ->add('body', 'textarea', array('label' => 'Сообщение'))
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
            ->add('user', null, array('label' => 'Пользователь'))
            ->add('subject', null, array('label' => 'Тема'))
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
            ->addIdentifier('subject', null, array('label' => 'Тема'))
            ->add('createdAt', null, array('label' => 'Дата и время'))
            ->add('user', null, array('label' => 'Пользователь'))
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
