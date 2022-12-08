<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Online;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OnlineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Online::class);
    }

    public function cleanup(\DateTime $lifetime): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM App\Entity\Online o WHERE o.datetime < :lifetime')
            ->setParameter('lifetime', $lifetime)
            ->execute();
    }
}
