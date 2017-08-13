<?php
namespace WapinetUserBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\MessageBundle\Event\MessageEvent;
use FOS\MessageBundle\Event\FOSMessageEvents as Event;
use FOS\MessageBundle\ModelManager\MessageManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use WapinetUserBundle\Entity\Event as EntityEvent;
use WapinetUserBundle\Entity\User;

class EventMessage implements EventSubscriberInterface
{
    private $messageManager;
    private $em;
    private $router;

    /**
     * @param MessageManagerInterface $messageManager
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     */
    public function __construct(MessageManagerInterface $messageManager, EntityManagerInterface $em, RouterInterface $router)
    {
        $this->messageManager = $messageManager;
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Event::POST_SEND => 'send',
        );
    }

    /**
     * @param MessageEvent $event
     */
    public function send(MessageEvent $event)
    {
        $message = $event->getMessage();
        $sender = $message->getSender();
        $participants = $message->getThread()->getOtherParticipants($sender);

        /** @var User $participant */
        foreach ($participants as $participant) {
            $entityEvent = new EntityEvent();
            $entityEvent->setSubject('Вам пришло новое сообщение.');
            $entityEvent->setTemplate('message');
            $entityEvent->setVariables(array(
                'message' => $message,
            ));
            $entityEvent->setNeedEmail($participant->getSubscriber()->getEmailMessages());
            $entityEvent->setUser($participant);

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }
}
