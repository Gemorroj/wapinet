<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Event as EntityEvent;
use App\Entity\File;
use App\Entity\UserFriend;
use App\Message\FileAddMessage;
use App\Repository\FileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FileAddHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileRepository $fileRepository,
    ) {
    }

    public function __invoke(FileAddMessage $message): void
    {
        /** @var File|null $file */
        $file = $this->fileRepository->find($message->fileId);
        if (!$file || !$file->getUser()) {
            return;
        }

        /** @var UserFriend $friend */
        foreach ($file->getUser()->getFriended() as $friend) {
            $entityEvent = new EntityEvent();
            if ($file->getUser()->isFemale()) {
                $entityEvent->setSubject('Ваша подруга '.$file->getUser()->getUsername().' добавила файл '.$file->getOriginalFileName().'.');
            } else {
                $entityEvent->setSubject('Ваш друг '.$file->getUser()->getUsername().' добавил файл '.$file->getOriginalFileName().'.');
            }
            $entityEvent->setTemplate('friend_file_add');
            $entityEvent->setVariables([
                'file' => $file,
            ]);
            $entityEvent->setNeedEmail($friend->getUser()->getSubscriber()?->isEmailFriends() ?? true);

            $entityEvent->setUser($friend->getUser());

            $this->entityManager->persist($entityEvent);
        }

        $this->entityManager->flush();
    }
}
