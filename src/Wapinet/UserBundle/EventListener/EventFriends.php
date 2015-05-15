<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\CommentBundle\Events as Event;
use FOS\CommentBundle\Event\CommentEvent;
use Wapinet\Bundle\Event\FileEvent;
use Wapinet\Bundle\Event\GistEvent;
use Wapinet\CommentBundle\Entity\Comment;
use Wapinet\UserBundle\Entity\Friend;
use Wapinet\UserBundle\Entity\Event as EntityEvent;
use Doctrine\Orm\EntityManager;
use Wapinet\UserBundle\Entity\User;
use Wapinet\UserBundle\Event\FriendEvent;

class EventFriends implements EventSubscriberInterface
{
    private $em;
    private $router;

    /**
     * @param EntityManager $em
     * @param Router $router
     */
    public function __construct(EntityManager $em, Router $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Event::COMMENT_POST_PERSIST => 'createComment',
            FriendEvent::FRIEND_ADD => 'friendAdd',
            FriendEvent::FRIEND_DELETE => 'friendDelete',
            FileEvent::FILE_ADD => 'fileAdd',
            GistEvent::GIST_ADD => 'gistAdd',
        );
    }

    /**
     * @param CommentEvent $event
     */
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
            $entityEvent = new EntityEvent();
            if (true === $friend->getFriend()->isFemale()) {
                $entityEvent->setSubject('Ваша подруга ' . $friend->getFriend()->getUsername() . ' оставила комментарий.');
            } else {
                $entityEvent->setSubject('Ваш друг ' . $friend->getFriend()->getUsername() . ' оставил комментарий.');
            }
            $entityEvent->setTemplate('friend_comment');
            $entityEvent->setVariables(array(
                'friend' => $friend->getFriend(),
                'comment' => $comment,
            ));
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }


    /**
     * @param FriendEvent $event
     */
    public function friendAdd(FriendEvent $event)
    {
        // Уведомление о добавлении  в друзья
        $entityEvent = new EntityEvent();
        if (true === $event->getUser()->isFemale()) {
            $entityEvent->setSubject('Вас добавила в друзья ' . $event->getUser()->getUsername() . '.');
        } else {
            $entityEvent->setSubject('Вас добавил в друзья ' . $event->getUser()->getUsername() . '.');
        }
        $entityEvent->setTemplate('friend_add');
        $entityEvent->setVariables(array(
            'friend' => $event->getUser(),
        ));
        $entityEvent->setNeedEmail($event->getFriend()->getSubscriber()->getEmailFriends());
        $entityEvent->setUser($event->getFriend());
        $this->em->persist($entityEvent);


        /** @var Friend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if (true === $event->getUser()->isFemale()) {
                $entityEvent->setSubject('Ваша подруга ' . $event->getUser()->getUsername() . ' добавила в друзья ' . $event->getFriend()->getUsername() . '.');
            } else {
                $entityEvent->setSubject('Ваш друг ' . $event->getUser()->getUsername() . ' добавил в друзья ' . $event->getFriend()->getUsername() . '.');
            }
            $entityEvent->setTemplate('friend_friend_add');
            $entityEvent->setVariables(array(
                'friend' => $event->getUser(),
                'friend_friend' => $event->getFriend(),
            ));
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }

    /**
     * @param FriendEvent $event
     */
    public function friendDelete(FriendEvent $event)
    {
        // Уведомление об удалении из друзей
        $entityEvent = new EntityEvent();
        if (true === $event->getUser()->isFemale()) {
            $entityEvent->setSubject('Вас удалила из друзей ' . $event->getUser()->getUsername() . '.');
        } else {
            $entityEvent->setSubject('Вас удалил из друзей ' . $event->getUser()->getUsername() . '.');
        }
        $entityEvent->setTemplate('friend_delete');
        $entityEvent->setVariables(array(
            'friend' => $event->getUser(),
        ));
        $entityEvent->setNeedEmail($event->getFriend()->getSubscriber()->getEmailFriends());
        $entityEvent->setUser($event->getFriend());
        $this->em->persist($entityEvent);

        /** @var Friend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if (true === $event->getUser()->isFemale()) {
                $entityEvent->setSubject('Ваша подруга ' . $event->getUser()->getUsername() . ' удалила из друзей ' . $event->getFriend()->getUsername() . '.');
            } else {
                $entityEvent->setSubject('Ваш друг ' . $event->getUser()->getUsername() . ' удалил из друзей ' . $event->getFriend()->getUsername() . '.');
            }
            $entityEvent->setTemplate('friend_friend_delete');
            $entityEvent->setVariables(array(
                'friend' => $event->getUser(),
                'friend_friend' => $event->getFriend(),
            ));
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }


    /**
     * @param FileEvent $event
     */
    public function fileAdd(FileEvent $event)
    {
        $user = $event->getUser();
        if (null === $user) {
            return;
        }

        /** @var Friend $friend */
        foreach ($user->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if (true === $user->isFemale()) {
                $entityEvent->setSubject('Ваша подруга ' . $user->getUsername() . ' добавила файл ' . $event->getFile()->getOriginalFileName() . '.');
            } else {
                $entityEvent->setSubject('Ваш друг ' . $user->getUsername() . ' добавил файл ' . $event->getFile()->getOriginalFileName() . '.');
            }
            $entityEvent->setTemplate('friend_file_add');
            $entityEvent->setVariables(array(
                'friend' => $user,
                'file' => $event->getFile(),
            ));
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }

    /**
     * @param GistEvent $event
     */
    public function gistAdd(GistEvent $event)
    {
        $user = $event->getUser();
        if (null === $user) {
            return;
        }

        /** @var Friend $friend */
        foreach ($user->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if (true === $user->isFemale()) {
                $entityEvent->setSubject('Ваша подруга ' . $user->getUsername() . ' добавила в блог сообщение с темой ' . $event->getGist()->getSubject() . '.');
            } else {
                $entityEvent->setSubject('Ваш друг ' . $user->getUsername() . ' добавил в блог сообщение с темой ' . $event->getGist()->getSubject() . '.');
            }
            $entityEvent->setTemplate('friend_gist_add');
            $entityEvent->setVariables(array(
                'friend' => $user,
                'gist' => $event->getGist(),
            ));
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }
}
