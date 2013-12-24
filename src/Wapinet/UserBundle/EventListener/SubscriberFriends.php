<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\CommentBundle\Events as Event;
use FOS\CommentBundle\Event\CommentEvent;
use Wapinet\CommentBundle\Entity\Comment;
use Wapinet\UserBundle\Entity\Subscriber as EntitySubscriber;
use Doctrine\Orm\EntityManager;
use Wapinet\UserBundle\Entity\User;

class SubscriberFriends implements EventSubscriberInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        // добавление в друзья
        // удаление из друзей
        // добавление файла
        return array(
            Event::COMMENT_POST_PERSIST => 'createComment',
        );
    }

    public function createComment(CommentEvent $event)
    {
        /** @var Comment $comment */
        $comment = $event->getComment();
        /** @var User $user */
        $user = $comment->getAuthor();
        if (null === $user || false === $user->getSubscribeFriends()) {
            return;
        }


        $thread = $comment->getThread();
        $path = $thread->getPermalink();

        /** @var User $friend */
        foreach ($user->getFriended() as $friend) {
            $subscriber = new EntitySubscriber();
            $subscriber->setSubject('Новый комментарий от ' . $friend->getUsername());
            $subscriber->setUrl($path);
            $subscriber->setMessage($comment->getBody());
            $subscriber->setUser($user);

            $this->em->persist($subscriber);
            $this->em->flush();
        }
    }
}
