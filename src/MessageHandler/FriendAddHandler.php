<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Event as EntityEvent;
use App\Entity\User;
use App\Entity\UserFriend;
use App\Message\FriendAddMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FriendAddHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(FriendAddMessage $message): void
    {
        /** @var User|null $user */
        $user = $this->userRepository->find($message->userId);
        /** @var User|null $friend */
        $friend = $this->userRepository->find($message->friendId);
        if (!$user || !$friend) {
            return;
        }

        // Уведомление о добавлении в друзья
        $entityEvent = new EntityEvent();
        if ($user->isFemale()) {
            $entityEvent->setSubject('Вас добавила в друзья '.$user->getUsername().'.');
        } else {
            $entityEvent->setSubject('Вас добавил в друзья '.$user->getUsername().'.');
        }
        $entityEvent->setTemplate('friend_add');
        $entityEvent->setVariables([
            'friend' => $user,
        ]);
        $entityEvent->setNeedEmail($friend->getSubscriber()?->isEmailFriends() ?? true);

        // fixme: avoid the stupid doctrine error
        if ($friend->getPanel()) {
            $this->entityManager->initializeObject($friend->getPanel());
        }
        if ($friend->getSubscriber()) {
            $this->entityManager->initializeObject($friend->getSubscriber());
        }
        $entityEvent->setUser($friend);
        $this->entityManager->persist($entityEvent);

        /** @var UserFriend $friended */
        foreach ($user->getFriended() as $friended) {
            if ($friended->getUser()->isEqualTo($friend)) {
                continue;
            }

            $entityEvent = new EntityEvent();

            if ($user->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$user->getUsername().' добавила в друзья '.$friend->getUsername().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$user->getUsername().' добавил в друзья '.$friend->getUsername().'.');
            }
            $entityEvent->setTemplate('friend_friend_add');
            $entityEvent->setVariables([
                'friend' => $user,
                'friend_friend' => $friend,
            ]);
            $entityEvent->setNeedEmail($friended->getUser()->getSubscriber()?->isEmailFriends() ?? true);

            // fixme: avoid the stupid doctrine error
            if ($friended->getUser()->getPanel()) {
                $this->entityManager->initializeObject($friended->getUser()->getPanel());
            }
            if ($friended->getUser()->getSubscriber()) {
                $this->entityManager->initializeObject($friended->getUser()->getSubscriber());
            }
            $entityEvent->setUser($friended->getUser());

            $this->entityManager->persist($entityEvent);
        }

        $this->entityManager->flush();
    }
}
