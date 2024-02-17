<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSubscriber>
 *
 * @method UserSubscriber|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSubscriber|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSubscriber[]    findAll()
 * @method UserSubscriber[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSubscriber::class);
    }
}
