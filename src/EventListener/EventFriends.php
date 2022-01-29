<?php

namespace App\EventListener;

use App\Entity\Event as EntityEvent;
use App\Entity\UserFriend;
use App\Event\FileEvent;
use App\Event\FriendEvent;
use App\Event\GistEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventFriends implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FriendEvent::FRIEND_ADD => 'friendAdd',
            FriendEvent::FRIEND_DELETE => 'friendDelete',
            FileEvent::FILE_ADD => 'fileAdd',
            GistEvent::GIST_ADD => 'gistAdd',
        ];
    }

    public function friendAdd(FriendEvent $event): void
    {
        // Уведомление о добавлении  в друзья
        $entityEvent = new EntityEvent();
        if ($event->getUser()->isFemale()) {
            $entityEvent->setSubject('Вас добавила в друзья '.$event->getUser()->getUsername().'.');
        } else {
            $entityEvent->setSubject('Вас добавил в друзья '.$event->getUser()->getUsername().'.');
        }
        $entityEvent->setTemplate('friend_add');
        $entityEvent->setVariables([
            'friend' => $event->getUser(),
        ]);
        $entityEvent->setNeedEmail($event->getFriend()->getSubscriber()->getEmailFriends());
        $entityEvent->setUser($event->getFriend());
        $this->em->persist($entityEvent);

        /** @var UserFriend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            if ($friend->getUser()->isEqualTo($event->getFriend())) {
                continue;
            }

            $entityEvent = new EntityEvent();

            if ($event->getUser()->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$event->getUser()->getUsername().' добавила в друзья '.$event->getFriend()->getUsername().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$event->getUser()->getUsername().' добавил в друзья '.$event->getFriend()->getUsername().'.');
            }
            $entityEvent->setTemplate('friend_friend_add');
            $entityEvent->setVariables([
                'friend' => $event->getUser(),
                'friend_friend' => $event->getFriend(),
            ]);
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }

    public function friendDelete(FriendEvent $event): void
    {
        // Уведомление об удалении из друзей
        $entityEvent = new EntityEvent();
        if ($event->getUser()->isFemale()) {
            $entityEvent->setSubject('Вас удалила из друзей '.$event->getUser()->getUsername().'.');
        } else {
            $entityEvent->setSubject('Вас удалил из друзей '.$event->getUser()->getUsername().'.');
        }
        $entityEvent->setTemplate('friend_delete');
        $entityEvent->setVariables([
            'friend' => $event->getUser(),
        ]);
        $entityEvent->setNeedEmail($event->getFriend()->getSubscriber()->getEmailFriends());
        $entityEvent->setUser($event->getFriend());
        $this->em->persist($entityEvent);

        /** @var UserFriend $friend */
        foreach ($event->getUser()->getFriended() as $friend) {
            if ($friend->getUser()->isEqualTo($event->getFriend())) {
                continue;
            }

            $entityEvent = new EntityEvent();
            if ($event->getUser()->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$event->getUser()->getUsername().' удалила из друзей '.$event->getFriend()->getUsername().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$event->getUser()->getUsername().' удалил из друзей '.$event->getFriend()->getUsername().'.');
            }
            $entityEvent->setTemplate('friend_friend_delete');
            $entityEvent->setVariables([
                'friend' => $event->getUser(),
                'friend_friend' => $event->getFriend(),
            ]);
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }

    public function fileAdd(FileEvent $event): void
    {
        $user = $event->getUser();
        if (null === $user) {
            return;
        }

        /** @var UserFriend $friend */
        foreach ($user->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if ($user->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$user->getUsername().' добавила файл '.$event->getFile()->getOriginalFileName().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$user->getUsername().' добавил файл '.$event->getFile()->getOriginalFileName().'.');
            }
            $entityEvent->setTemplate('friend_file_add');
            $entityEvent->setVariables([
                'friend' => $user,
                'file' => $event->getFile(),
            ]);
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }

    public function gistAdd(GistEvent $event): void
    {
        $user = $event->getUser();
        if (null === $user) {
            return;
        }

        /** @var UserFriend $friend */
        foreach ($user->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if ($user->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$user->getUsername().' добавила в блог сообщение с темой '.$event->getGist()->getSubject().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$user->getUsername().' добавил в блог сообщение с темой '.$event->getGist()->getSubject().'.');
            }
            $entityEvent->setTemplate('friend_gist_add');
            $entityEvent->setVariables([
                'friend' => $user,
                'gist' => $event->getGist(),
            ]);
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()->getEmailFriends());
            $entityEvent->setUser($friend->getUser());

            $this->em->persist($entityEvent);
        }

        $this->em->flush();
    }
}
