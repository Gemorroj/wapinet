<?php

namespace App\EventListener;

use App\Entity\Online;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class OnlineListener
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onCoreController(FilterControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
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
        $online = $this->em->getRepository(Online::class)->findOneBy(['ip' => $requestIp, 'browser' => $requestBrowser]);

        if (null === $online) {
            $online = new Online();
            $online->setBrowser($requestBrowser);
            $online->setIp($requestIp);
        }
        $online->setDatetime(new DateTime());
        $online->setPath($request->getPathInfo());

        $this->em->persist($online);

        try {
            $this->em->flush();
        } catch (Exception $e) {
            // могут быть конкурентные запросы, которые запишут в онлайн данные на уникальном индексе
            // игнорируем, т.к. маловажно
        }
    }

    private function cleanupOnline(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Online o WHERE o.datetime < :lifetime')
            ->setParameter('lifetime', new DateTime('now -'.User::LIFETIME))
            ->execute();
    }
}
