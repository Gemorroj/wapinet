<?php
namespace Wapinet\UserBundle\EventListener;

use FOS\UserBundle\Model\UserManagerInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Wapinet\UserBundle\Entity\User;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    private $userManager;

    /**
     * EasyAdminSubscriber constructor.
     * @param UserManagerInterface $userManager
     */
    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            EasyAdminEvents::PRE_UPDATE => array('setUserCanonicalFields'),
        );
    }


    /**
     * @param GenericEvent $event
     */
    public function setUserCanonicalFields(GenericEvent $event)
    {
        $entity = $event->getSubject();

        if (!($entity instanceof User)) {
            return;
        }

        $this->userManager->updateCanonicalFields($entity);
        $this->userManager->updatePassword($entity);

        $event['entity'] = $entity;
    }
}
