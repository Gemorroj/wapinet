<?php
namespace Wapinet\UserBundle\EventListener;

use FOS\MessageBundle\Model\ParticipantInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\MessageBundle\Event\MessageEvent;
use FOS\MessageBundle\Event\ReadableEvent;
use FOS\MessageBundle\Event\ThreadEvent;
use FOS\MessageBundle\Event\FOSMessageEvents as Event;
use FOS\MessageBundle\ModelManager\MessageManagerInterface;
use Wapinet\UserBundle\Entity\Subscriber as EntitySubscriber;
use Doctrine\Orm\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

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
            Event::POST_DELETE => 'delete',
            Event::POST_READ => 'read',
        );
    }

    public function send(MessageEvent $event)
    {
        $message = $event->getMessage();
        $sender = $message->getSender();
        /** @var ParticipantInterface[] $participants */
        $participants = $message->getThread()->getOtherParticipants($sender);
        $threadId = $message->getThread()->getId();
        $path = $this->router->generate('wapinet_message_thread_view', array('threadId' => $threadId), Router::ABSOLUTE_URL);

        foreach ($participants as $participant) {
            $user = $this->em->find('Wapinet\UserBundle\Entity\User', $participant->getId());

            if ($user->getSubscribeMessages()) {
                $subscriber = new EntitySubscriber();
                $subscriber->setSubject('Новое сообщение');
                $subscriber->setUrl($path);
                $subscriber->setMessage($message->getBody());
                $subscriber->setUser($user);

                $this->em->persist($subscriber);
            }
        }
        $this->em->flush();
    }
}
