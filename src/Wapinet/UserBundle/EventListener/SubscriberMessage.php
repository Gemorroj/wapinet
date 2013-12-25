<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\MessageBundle\Event\MessageEvent;
use FOS\MessageBundle\Event\FOSMessageEvents as Event;
use FOS\MessageBundle\ModelManager\MessageManagerInterface;
use Wapinet\UserBundle\Entity\Subscriber as EntitySubscriber;
use Doctrine\Orm\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Wapinet\UserBundle\Entity\User;

class SubscriberMessage implements EventSubscriberInterface
{
    private $messageManager;
    private $em;
    private $router;

    public function __construct(MessageManagerInterface $messageManager, EntityManager $em, Router $router)
    {
        $this->messageManager = $messageManager;
        $this->em = $em;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Event::POST_SEND => 'send',
        );
    }

    public function send(MessageEvent $event)
    {
        $message = $event->getMessage();
        $sender = $message->getSender();
        $participants = $message->getThread()->getOtherParticipants($sender);

        /** @var User $participant */
        foreach ($participants as $participant) {
            $subscriber = new EntitySubscriber();
            $subscriber->setSubject('Вам пришло новое сообщение.');
            $subscriber->setTemplate('message');
            $subscriber->setVariables(array(
                'message' => $message,
            ));
            $subscriber->setNeedEmail($participant->getEmailMessages());
            $subscriber->setUser($participant);

            $this->em->persist($subscriber);
        }

        $this->em->flush();
    }
}
