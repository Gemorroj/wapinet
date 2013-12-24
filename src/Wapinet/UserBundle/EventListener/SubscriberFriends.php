<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\CommentBundle\Events as Event;
use FOS\CommentBundle\Event\CommentEvent;
use Wapinet\Bundle\Event\FileEvent;
use Wapinet\CommentBundle\Entity\Comment;
use Wapinet\UserBundle\Entity\Friend;
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
        return array(
            Event::COMMENT_POST_PERSIST => 'createComment',
            FriendEvent::FRIEND_ADD => 'friendAdd',
            FriendEvent::FRIEND_DELETE => 'friendDelete',
            FileEvent::FILE_ADD => 'fileAdd',
        );
    }

    public function createComment(CommentEvent $event)
    {
        /** @var Comment $comment */
        $comment = $event->getComment();
        /** @var User $user */
        $user = $comment->getAuthor();
        if (null === $user) {
            return;
        }


        $thread = $comment->getThread();
        $path = $thread->getPermalink();

        /** @var Friend $friend */
        foreach ($user->getFriended() as $friend) {
            if (true === $friend->getUser()->getSubscribeFriends()) {
                $subscriber = new EntitySubscriber();
                $subscriber->setSubject('Новый комментарий от ' . $user->getUsername());
                $subscriber->setUrl($path);
                $subscriber->setMessage($comment->getBody());
                $subscriber->setUser($friend->getUser());

                $this->em->persist($subscriber);
            }
        }

        $this->em->flush();
    }


    public function friendAdd(FriendEvent $event)
    {
        $urlFriend = $this->router->generate('wapinet_user_profile', array('username' => $event->getFriend()->getUsername()), Router::ABSOLUTE_URL);
        $urlUser = $this->router->generate('wapinet_user_profile', array('username' => $event->getUser()->getUsername()), Router::ABSOLUTE_URL);

        // Уведомление о добавлении  в друзья
        $subscriber = new EntitySubscriber();
        $subscriber->setSubject($event->getUser()->getUsername() . ' добавил Вас в друзья');
        $subscriber->setUrl($urlUser);
        $subscriber->setMessage('Пользователь ' . $event->getUser()->getUsername() . ' ( ' . $urlUser . ' ) добавил Вас в друзья.');
        $subscriber->setUser($event->getFriend());
        $this->em->persist($subscriber);


        /** @var Friend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            if (true === $friend->getUser()->getSubscribeFriends()) {
                $subscriber = new EntitySubscriber();
                $subscriber->setSubject($event->getUser()->getUsername() . ' добавил в друзья ' . $event->getFriend()->getUsername());
                $subscriber->setUrl($urlUser);
                $subscriber->setMessage('Ваш друг ' . $event->getUser()->getUsername() . ' ( ' . $urlUser . ' ) добавил в друзья ' . $event->getFriend()->getUsername() . '( ' . $urlFriend . ' ).');
                $subscriber->setUser($friend->getUser());

                $this->em->persist($subscriber);
            }
        }

        $this->em->flush();
    }

    public function friendDelete(FriendEvent $event)
    {
        $urlFriend = $this->router->generate('wapinet_user_profile', array('username' => $event->getFriend()->getUsername()), Router::ABSOLUTE_URL);
        $urlUser = $this->router->generate('wapinet_user_profile', array('username' => $event->getUser()->getUsername()), Router::ABSOLUTE_URL);

        // Уведомление об удалении из друзей
        $subscriber = new EntitySubscriber();
        $subscriber->setSubject($event->getUser()->getUsername() . ' удалил Вас из друзей');
        $subscriber->setUrl($urlUser);
        $subscriber->setMessage('Пользователь ' . $event->getUser()->getUsername() . ' ( ' . $urlUser . ' ) удалил Вас из друзей.');
        $subscriber->setUser($event->getFriend());
        $this->em->persist($subscriber);

        /** @var Friend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            if (true === $friend->getUser()->getSubscribeFriends()) {
                $subscriber = new EntitySubscriber();
                $subscriber->setSubject($event->getUser()->getUsername() . ' удалил из друзей ' . $event->getFriend()->getUsername());
                $subscriber->setUrl($urlUser);
                $subscriber->setMessage('Ваш друг ' . $event->getUser()->getUsername() . ' ( ' . $urlUser . ' ) удалил из друзей ' . $event->getFriend()->getUsername() . ' ( ' . $urlFriend . ').');
                $subscriber->setUser($friend->getUser());

                $this->em->persist($subscriber);
            }
        }

        $this->em->flush();
    }


    public function fileAdd(FileEvent $event)
    {
        $url = $this->router->generate('file_view', array('id' => $event->getFile()->getId()), Router::ABSOLUTE_URL);

        /** @var Friend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            if (true === $friend->getUser()->getSubscribeFriends()) {
                $subscriber = new EntitySubscriber();
                $subscriber->setSubject($event->getUser()->getUsername() . ' добавил файл ' . $event->getFile()->getOriginalFileName());
                $subscriber->setUrl($url);
                $subscriber->setMessage('Ваш друг ' . $event->getUser()->getUsername() . ' загрузил файл "' . $event->getFile()->getOriginalFileName() . '".');
                $subscriber->setUser($friend->getUser());

                $this->em->persist($subscriber);
            }
        }

        $this->em->flush();
    }
}
