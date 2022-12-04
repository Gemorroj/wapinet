<?php

namespace App\EventListener;

use App\Entity\Online;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class OnlineListener
{
    public function __construct(private EntityManagerInterface $entityManager, private ManagerRegistry $managerRegistry)
    {
    }

    public function onCoreController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // чистим случайным образом, чтобы разгрузить БД
        if (1 === \mt_rand(1, 10)) {
            $this->cleanupOnline();
        }

        $request = $event->getRequest();

        $requestIp = $request->getClientIp();
        $requestBrowser = $request->headers->get('User-Agent', '');

        /** @var Online|null $online */
        $online = $this->entityManager->getRepository(Online::class)->findOneBy([
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
            // Могут быть конкурентные запросы, которые запишут в онлайн данные на уникальном индексе
            // игнорируем, т.к. маловажно
            $this->managerRegistry->resetManager();
        }
    }

    private function cleanupOnline(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Online o WHERE o.datetime < :lifetime')
            ->setParameter('lifetime', new \DateTime('now -'.User::LIFETIME))
            ->execute();
    }
}
