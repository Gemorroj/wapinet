<?php

namespace Wapinet\NewsBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Wapinet\NewsBundle\Entity\News;

class NewsAdmin extends Admin
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
     * @throws \PDOException
     * @param News $news
     * @return bool
     */
    public function postPersist($news)
    {
        $connection = $this->container->get('doctrine.orm.entity_manager')->getConnection();
        $url = $this->container->get('router')->generate('wapinet_news_show', array('id' => $news->getId()), Router::ABSOLUTE_URL);
        $subject = 'Новости сайта ' . $this->container->getParameter('wapinet_title');
        $message = $news->getSubject() . "\r\n\r\n" . $news->getBody();

        $q = $connection->prepare('
            INSERT INTO subscriber(user_id, subject, message, url)
            SELECT fos_user.id, :subject, :message, :url
            FROM fos_user
            WHERE fos_user.enabled = 1 AND fos_user.locked = 0 AND fos_user.expired = 0 AND fos_user.subscribe_news = 1
        ');

        $q->bindValue('subject', $subject);
        $q->bindValue('message', $message);
        $q->bindValue('url', $url);

        $result = $q->execute();

        if (false === $result) {
            $exception = new \PDOException('Ошибка при добавлении подписчиков.');
            $exception->errorInfo = $q->errorInfo();

            $this->container->get('logger')->critical('Ошибка при добавлении подписчиков.', $exception->errorInfo);
            //throw new $exception;
        }

        return $result;
    }
}
