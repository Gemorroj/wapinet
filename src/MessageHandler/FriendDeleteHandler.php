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
readonly class FriendDeleteHandler
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

        // Уведомление об удалении из друзей
        $entityEvent = new EntityEvent();
        if ($user->isFemale()) {
            $entityEvent->setSubject('Вас удалила из друзей '.$user->getUsername().'.');
        } else {
            $entityEvent->setSubject('Вас удалил из друзей '.$user->getUsername().'.');
        }
        $entityEvent->setTemplate('friend_delete');
        $entityEvent->setVariables([
            'friend' => $user,
        ]);
        $entityEvent->setNeedEmail($friend->getSubscriber()?->isEmailFriends() ?? true);

        $entityEvent->setUser($friend);
        $this->entityManager->persist($entityEvent);

        /** @var UserFriend $friended */
        foreach ($user->getFriended() as $friended) {
            if ($friended->getUser()->isEqualTo($friend)) {
                continue;
            }

            $entityEvent = new EntityEvent();
            if ($user->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$user->getUsername().' удалила из друзей '.$friend->getUsername().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$user->getUsername().' удалил из друзей '.$friend->getUsername().'.');
            }
            $entityEvent->setTemplate('friend_friend_delete');
            $entityEvent->setVariables([
                'friend' => $user,
                'friend_friend' => $friend,
            ]);
            $entityEvent->setNeedEmail($friended->getUser()->getSubscriber()?->isEmailFriends() ?? true);

            $entityEvent->setUser($friended->getUser());

            $this->entityManager->persist($entityEvent);
        }

        $this->entityManager->flush();
    }
}
