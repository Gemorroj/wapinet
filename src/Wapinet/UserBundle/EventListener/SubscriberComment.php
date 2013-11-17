<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\CommentBundle\Events as Event;
use FOS\CommentBundle\Event\CommentEvent;
use Wapinet\UserBundle\Entity\Subscriber as EntitySubscriber;
use Doctrine\Orm\EntityManager;
use Wapinet\UserBundle\Entity\User;

class SubscriberComment implements EventSubscriberInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Event::COMMENT_POST_PERSIST => 'create',
        );
    }

    public function create(CommentEvent $event)
    {
        $comment = $event->getComment();
        $parentComment = $comment->getParent();
        if (null === $parentComment) {
            return;
        }

        /** @var User $user */
        $user = $parentComment->getAuthor();
        if (null === $user) {
            return;
        }

        $thread = $comment->getThread();
        $path = $thread->getPermalink();

        if (null !== $user && $user->getSubscribeComments()) {
            $subscriber = new EntitySubscriber();
            $subscriber->setSubject('Новый комментарий');
            $subscriber->setUrl($path);
            $subscriber->setMessage($comment->getBody());
            $subscriber->setUser($user);

            $this->em->persist($subscriber);
            $this->em->flush();
        }
    }

    //public function read(ReadableEvent $event)
    //{
    //    // TODO:unsubscribe
    //}

    //public function delete(ThreadEvent $event)
    //{
    //    // TODO:unsubscribe
    //}
}
