<?php

namespace App\EventListener;

use App\Entity\Online;
use App\Entity\User;
use App\Repository\OnlineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener(priority: -1)]
class OnlineListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $managerRegistry,
        private OnlineRepository $onlineRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // чистим случайным образом, чтобы разгрузить БД
        if (1 === \mt_rand(1, 10)) {
            $this->onlineRepository->cleanup(new \DateTime('now -'.User::LIFETIME));
        }

        $request = $event->getRequest();

        $requestIp = $request->getClientIp();
        $requestBrowser = $request->headers->get('User-Agent', '');

        /** @var Online|null $online */
        $online = $this->onlineRepository->findOneBy([
            'ip' => $requestIp,
            'browser' => $requestBrowser,
        ]);

        if (null === $online) {
            $online = new Online();
            $online->setBrowser($requestBrowser);
            $online->setIp($requestIp);
        }
        $online->setDatetime(new \DateTime());
        $online->setPath($request->getPathInfo());

        try {
            $this->entityManager->persist($online);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->managerRegistry->resetManager();
            $this->logger->error('Не удалось записать online', ['exception' => $e]);
        }
    }
}
