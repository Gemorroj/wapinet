<?php

namespace App\Repository;

use App\Entity\Friend;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class FriendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friend::class);
    }

    public function getFriendsQuery(User $user): Query
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->innerJoin('f.friend', 'u', Join::WITH, 'u.enabled = 1')
            ->orderBy('u.lastActivity', 'DESC')
            ->addOrderBy('u.username', 'ASC')
            ->getQuery();
    }

    public function getFriendsCount(User $user, ?\DateTime $lastActivity = null): int
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->innerJoin('f.friend', 'u', Join::WITH, 'u.enabled = 1');

        if (null !== $lastActivity) {
            $queryBuilder->andWhere('u.lastActivity > :lastActivity');
            $queryBuilder->setParameter('lastActivity', $lastActivity);
        }

        $q = $queryBuilder->getQuery();

        return $q->getSingleScalarResult();
    }

    public function getFriend(User $user, User $friend): ?Friend
    {
        return $this->findOneBy([
            'user' => $user,
            'friend' => $friend,
        ]);
    }
}
