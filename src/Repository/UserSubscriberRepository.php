<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSubscriber::class);
    }
}
