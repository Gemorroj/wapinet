<?php

namespace Wapinet\Bundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Wapinet\Bundle\Entity\News;
use Wapinet\UserBundle\Entity\Event as EntityEvent;

class NewsAdmin extends Admin
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $baseRouteName = 'sonata_news';
    protected $baseRoutePattern = 'news';
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    );

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('createdBy', null, array('label' => 'Автор'))
            ->add('subject', null, array('label' => 'Заголовок'))
            ->add('createdAt', 'doctrine_orm_datetime_range', array('label' => 'Создано'), null, array('widget' => 'single_text'))
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('subject', null, array('label' => 'Заголовок'))
            ->add('createdAt', null, array('label' => 'Создано'))
            ->add('updatedAt', null, array('label' => 'Обновлено'))
            ->add('createdBy', null, array('label' => 'Автор'))
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

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('subject', null, array('label' => 'Заголовок'))
            ->add('body', null, array('label' => 'Новость'))
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'Идентификатор'))
            ->add('subject', null, array('label' => 'Заголовок'))
            ->add('body', null, array('label' => 'Новость'))
            ->add('createdAt', null, array('label' => 'Создано'))
            ->add('updatedAt', null, array('label' => 'Обновлено'))
            ->add('createdBy', null, array('label' => 'Автор'))
        ;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return NewsAdmin
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }


    /**
     * Подписка
     *
     * @param News $news
     */
    public function postPersist($news)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $userRepository = $em->getRepository('WapinetUserBundle:User');
        $users = $userRepository->findBy(array(
            'enabled' => true,
            'locked' => false,
            'expired' => false,
        ));

        foreach ($users as $user) {
            $entityEvent = new EntityEvent();
            $entityEvent->setSubject('Новость на сайте.');
            $entityEvent->setTemplate('news');
            $entityEvent->setVariables(array(
                'news' => $news,
            ));

            $entityEvent->setNeedEmail($user->getSubscriber()->getEmailNews());
            $entityEvent->setUser($user);

            $em->persist($entityEvent);
        }

        $em->flush();
    }
}
