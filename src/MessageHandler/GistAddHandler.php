<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Event as EntityEvent;
use App\Entity\Gist;
use App\Entity\UserFriend;
use App\Message\GistAddMessage;
use App\Repository\GistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GistAddHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GistRepository $gistRepository,
    ) {
    }

    public function __invoke(GistAddMessage $message): void
    {
        /** @var Gist|null $gist */
        $gist = $this->gistRepository->find($message->gistId);
        if (!$gist || !$gist->getUser()) {
            return;
        }

        /** @var UserFriend $friend */
        foreach ($gist->getUser()->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if ($gist->getUser()->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$gist->getUser()->getUsername().' добавила в блог сообщение с темой '.$gist->getSubject().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$gist->getUser()->getUsername().' добавил в блог сообщение с темой '.$gist->getSubject().'.');
            }
            $entityEvent->setTemplate('friend_gist_add');
            $entityEvent->setVariables([
                'gist' => $gist,
            ]);
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()?->isEmailFriends() ?? true);

            $entityEvent->setUser($friend->getUser());

            $this->entityManager->persist($entityEvent);
        }

        $this->entityManager->flush();
    }
}
