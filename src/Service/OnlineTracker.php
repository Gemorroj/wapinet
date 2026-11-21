<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Online;
use App\Entity\User;
use App\Repository\OnlineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class OnlineTracker
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OnlineRepository $onlineRepository,
        private ClockInterface $clock,
    ) {
    }

    public function track(Request $request): void
    {
        $now = $this->clock->now();

        $this->onlineRepository->cleanup($now->modify('-'.User::LIFETIME));

        $online = $this->onlineRepository->findOneByIpAndBrowser(
            $request->getClientIp() ?? '',
            (string) $request->headers->get('User-Agent', ''),
        );

        if (null === $online) {
            $online = new Online();
            $online->setIp($request->getClientIp() ?? '');
            $online->setBrowser((string) $request->headers->get('User-Agent', ''));
        }

        $online->setDatetime($now);
        $online->setPath($request->getPathInfo());

        $this->entityManager->persist($online);
        $this->entityManager->flush();
    }
}
