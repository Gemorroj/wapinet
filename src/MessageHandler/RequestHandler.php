<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\RequestMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RequestHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        #[Autowire('%wapinet_user_last_activity_delay%')]
        private int $lastActivityDelay,
    ) {
    }

    public function __invoke(RequestMessage $message): void
    {
        if ($message->userIdentifier) {
            $user = $this->userRepository->loadUserByIdentifier($message->userIdentifier);

            if ($user) {
                $lastActivity = $user->getLastActivity();
                $delay = new \DateTime($this->lastActivityDelay.' seconds ago');

                if (null === $lastActivity || ($lastActivity < $delay && $lastActivity < $message->dateTime)) {
                    $user->setLastActivity($message->dateTime);
                    $this->entityManager->persist($user);
                }
            }
        }

        $this->entityManager->flush();
    }
}
