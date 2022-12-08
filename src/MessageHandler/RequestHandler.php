<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Online;
use App\Entity\User;
use App\Message\RequestMessage;
use App\Repository\OnlineRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RequestHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private OnlineRepository $onlineRepository,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function __invoke(RequestMessage $message): void
    {
        /** @var User|null $user */
        $user = $this->userRepository->loadUserByIdentifier($message->userIdentifier);

        if ($user) {
            $delay = new \DateTime($this->parameterBag->get('wapinet_user_last_activity_delay').' seconds ago');
            if ($user->getLastActivity() < $delay) {
                $user->setLastActivity(new \DateTime());
                $this->entityManager->persist($user);
            }
        }

        $this->onlineRepository->cleanup(new \DateTime('now -'.User::LIFETIME));
        $online = $this->onlineRepository->findOneByIpAndBrowser(
            $message->ip,
            $message->browser,
        );

        if (!$online) {
            $online = new Online();
            $online->setIp($message->ip);
            $online->setBrowser($message->browser);
        }
        $online->setDatetime($message->dateTime);
        $online->setPath($message->path);

        $this->entityManager->persist($online);

        $this->entityManager->flush();
    }
}
