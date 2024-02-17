<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Event;
use App\Entity\News;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: '__invoke', entity: News::class)]
final readonly class NewsPostPersistListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(News $entity, PostPersistEventArgs $event): void
    {
        /** @var User[] $users */
        $users = $this->userRepository->findBy([
            'enabled' => true,
        ]);

        foreach ($users as $user) {
            $entityEvent = new Event();
            $entityEvent->setSubject('Новость на сайте.');
            $entityEvent->setTemplate('news');
            $entityEvent->setVariables([
                'news' => $entity,
            ]);

            $entityEvent->setNeedEmail($user->getSubscriber()?->isEmailNews() ?? true);
            $entityEvent->setUser($user);

            $this->entityManager->persist($entityEvent);
        }
        $this->entityManager->flush();
    }
}
