<?php
namespace Wapinet\Bundle\EventListener;

use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Wapinet\Bundle\Entity\File;
use Wapinet\Bundle\Entity\Gist;
use Wapinet\Bundle\Entity\News;
use Wapinet\UserBundle\Entity\Event;
use Wapinet\UserBundle\Entity\User;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    private $container;

    /**
     * EasyAdminSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            EasyAdminEvents::POST_PERSIST => array('setPersistSubscriber'),
            EasyAdminEvents::PRE_REMOVE => array('setRemoveSubscriber'),
        );
    }


    /**
     * @param GenericEvent $event
     */
    public function setPersistSubscriber(GenericEvent $event)
    {
        $entity = $event->getSubject();

        if ($entity instanceof News) {
            $entity->setCreatedBy($this->container->get('security.token_storage')->getToken()->getUser());
            $this->newsSubscriber($entity);

            $event['entity'] = $entity;
        }
    }


    /**
     * @param GenericEvent $event
     */
    public function setRemoveSubscriber(GenericEvent $event)
    {
        $entity = $event->getSubject();

        if ($entity instanceof News) {
            $this->container->get('wapinet_comment.helper')->removeThread('news-' . $entity->getId());

            $event['entity'] = $entity;
        }

        if ($entity instanceof File) {
            $this->container->get('file')->cleanupFile($entity);

            $event['entity'] = $entity;
        }

        if ($entity instanceof Gist) {
            $this->container->get('wapinet_comment.helper')->removeThread('gist-' . $entity->getId());

            $event['entity'] = $entity;
        }
    }

    /**
     * Подписка на новости
     *
     * @param News $news
     */
    private function newsSubscriber(News $news)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $userRepository = $em->getRepository('WapinetUserBundle:User');
        $users = $userRepository->findBy(array(
            'enabled' => true,
            'locked' => false,
            'expired' => false,
        ));

        foreach ($users as $user) {
            $entityEvent = new Event();
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
