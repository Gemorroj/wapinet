<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\CommentBundle\Events as Event;
use FOS\CommentBundle\Event\CommentEvent;
use Wapinet\CommentBundle\Entity\Comment;
use Wapinet\UserBundle\Entity\Subscriber as EntitySubscriber;
use Doctrine\Orm\EntityManager;
use Wapinet\UserBundle\Entity\User;
use Wapinet\UserBundle\Event\FriendEvent;

class SubscriberFriends implements EventSubscriberInterface
{
    private $em;
    private $router;

    public function __construct(EntityManager $em, Router $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        // добавление файла
        return array(
            Event::COMMENT_POST_PERSIST => 'createComment',
            FriendEvent::FRIEND_ADD => 'friendAdd',
            FriendEvent::FRIEND_DELETE => 'friendDelete',
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
            $subscriber->setSubject('Новый комментарий от ' . $user->getUsername());
            $subscriber->setUrl($path);
            $subscriber->setMessage($comment->getBody());
            $subscriber->setUser($friend);

            $this->em->persist($subscriber);
            $this->em->flush();
        }
    }


    public function friendAdd(FriendEvent $event)
    {
        $urlFriend = $this->router->generate('wapinet_user_profile', array('username' => $event->getFriend()->getUsername()), Router::ABSOLUTE_URL);
        $urlUser = $this->router->generate('wapinet_user_profile', array('username' => $event->getUser()->getUsername()), Router::ABSOLUTE_URL);

        /** @var User $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            $subscriber = new EntitySubscriber();
            $subscriber->setSubject($event->getUser()->getUsername() . ' добавил в друзья ' . $event->getFriend()->getUsername());
            $subscriber->setUrl($urlUser);
            $subscriber->setMessage('Ваш друг ' . $event->getUser()->getUsername() . ' ( ' . $urlUser . ' ) добавил в друзья ' . $event->getFriend()->getUsername() . '( ' . $urlFriend . ' ).');
            $subscriber->setUser($friend);

            $this->em->persist($subscriber);
            $this->em->flush();
        }
    }

    public function friendDelete(FriendEvent $event)
    {
        $urlFriend = $this->router->generate('wapinet_user_profile', array('username' => $event->getFriend()->getUsername()), Router::ABSOLUTE_URL);
        $urlUser = $this->router->generate('wapinet_user_profile', array('username' => $event->getUser()->getUsername()), Router::ABSOLUTE_URL);

        /** @var User $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            $subscriber = new EntitySubscriber();
            $subscriber->setSubject($event->getUser()->getUsername() . ' удалил из друзей ' . $event->getFriend()->getUsername());
            $subscriber->setUrl($urlUser);
            $subscriber->setMessage('Ваш друг ' . $event->getUser()->getUsername() . ' ( ' . $urlUser . ' ) удалил из друзей ' . $event->getFriend()->getUsername() . ' ( ' . $urlFriend . ').');
            $subscriber->setUser($friend);

            $this->em->persist($subscriber);
            $this->em->flush();
        }
    }
}
