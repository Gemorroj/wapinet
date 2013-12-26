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

        /** @var Friend $friend */
        foreach ($user->getFriended() as $friend) {
            $subscriber = new EntitySubscriber();
            if (true === $friend->getFriend()->isWoman()) {
                $subscriber->setSubject('Ваша подруга ' . $friend->getFriend()->getUsername() . ' оставила комментарий.');
            } else {
                $subscriber->setSubject('Ваш друг ' . $friend->getFriend()->getUsername() . ' оставил комментарий.');
            }
            $subscriber->setTemplate('friend_comment');
            $subscriber->setVariables(array(
                'friend' => $friend->getFriend(),
                'comment' => $comment,
            ));
            $subscriber->setNeedEmail($friend->getUser()->getEmailFriends());
            $subscriber->setUser($friend->getUser());

            $this->em->persist($subscriber);
        }

        $this->em->flush();
    }


    public function friendAdd(FriendEvent $event)
    {
        // Уведомление о добавлении  в друзья
        $subscriber = new EntitySubscriber();
        if (true === $event->getUser()->isWoman()) {
            $subscriber->setSubject('Вас добавила в друзья ' . $event->getUser()->getUsername() . '.');
        } else {
            $subscriber->setSubject('Вас добавил в друзья ' . $event->getUser()->getUsername() . '.');
        }
        $subscriber->setTemplate('friend_add');
        $subscriber->setVariables(array(
            'friend' => $event->getUser(),
        ));
        $subscriber->setNeedEmail($event->getFriend()->getEmailFriends());
        $subscriber->setUser($event->getFriend());
        $this->em->persist($subscriber);


        /** @var Friend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            $subscriber = new EntitySubscriber();
            if (true === $event->getUser()->isWoman()) {
                $subscriber->setSubject('Ваша подруга ' . $event->getUser()->getUsername() . ' добавила в друзья ' . $event->getFriend()->getUsername() . '.');
            } else {
                $subscriber->setSubject('Ваш друг ' . $event->getUser()->getUsername() . ' добавил в друзья ' . $event->getFriend()->getUsername() . '.');
            }
            $subscriber->setTemplate('friend_friend_add');
            $subscriber->setVariables(array(
                'friend' => $event->getUser(),
                'friend_friend' => $event->getFriend(),
            ));
            $subscriber->setNeedEmail($friend->getUser()->getEmailFriends());
            $subscriber->setUser($friend->getUser());

            $this->em->persist($subscriber);
        }

        $this->em->flush();
    }

    public function friendDelete(FriendEvent $event)
    {
        // Уведомление об удалении из друзей
        $subscriber = new EntitySubscriber();
        if (true === $event->getUser()->isWoman()) {
            $subscriber->setSubject('Вас удалила из друзей ' . $event->getUser()->getUsername() . '.');
        } else {
            $subscriber->setSubject('Вас удалил из друзей ' . $event->getUser()->getUsername() . '.');
        }
        $subscriber->setTemplate('friend_delete');
        $subscriber->setVariables(array(
            'friend' => $event->getUser(),
        ));
        $subscriber->setNeedEmail($event->getFriend()->getEmailFriends());
        $subscriber->setUser($event->getFriend());
        $this->em->persist($subscriber);

        /** @var Friend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            $subscriber = new EntitySubscriber();
            if (true === $event->getUser()->isWoman()) {
                $subscriber->setSubject('Ваша подруга ' . $event->getUser()->getUsername() . ' удалила из друзей ' . $event->getFriend()->getUsername() . '.');
            } else {
                $subscriber->setSubject('Ваш друг ' . $event->getUser()->getUsername() . ' удалил из друзей ' . $event->getFriend()->getUsername() . '.');
            }
            $subscriber->setTemplate('friend_friend_delete');
            $subscriber->setVariables(array(
                'friend' => $event->getUser(),
                'friend_friend' => $event->getFriend(),
            ));
            $subscriber->setNeedEmail($friend->getUser()->getEmailFriends());
            $subscriber->setUser($friend->getUser());

            $this->em->persist($subscriber);
        }

        $this->em->flush();
    }


    public function fileAdd(FileEvent $event)
    {
        $user = $event->getUser();
        if (null === $user) {
            return;
        }

        /** @var Friend $friend */
        foreach ($user->getFriended() as $friend) {
            $subscriber = new EntitySubscriber();
            if (true === $user->isWoman()) {
                $subscriber->setSubject('Ваша подруга ' . $user->getUsername() . ' добавила файл ' . $event->getFile()->getOriginalFileName() . '.');
            } else {
                $subscriber->setSubject('Ваш друг ' . $user->getUsername() . ' добавил файл ' . $event->getFile()->getOriginalFileName() . '.');
            }
            $subscriber->setTemplate('friend_file_add');
            $subscriber->setVariables(array(
                'friend' => $user,
                'file' => $event->getFile(),
            ));
            $subscriber->setNeedEmail($friend->getUser()->getEmailFriends());
            $subscriber->setUser($friend->getUser());

            $this->em->persist($subscriber);
        }

        $this->em->flush();
    }
}
