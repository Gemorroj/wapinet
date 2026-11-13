<?php

namespace App\Service\Api\File;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class FileViewTracker
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function track(File $file): void
    {
        $now = $this->clock->now();

        $this->entityManager->createQueryBuilder()
            ->update(File::class, 'f')
            ->set('f.countViews', 'f.countViews + 1')
            ->set('f.lastViewAt', ':now')
            ->where('f.id = :id')
            ->setParameter('now', $now)
            ->setParameter('id', $file->getId())
            ->getQuery()
            ->execute();

        $file->setCountViews($file->getCountViews() + 1);
        $file->setLastViewAt($now);
    }
}
