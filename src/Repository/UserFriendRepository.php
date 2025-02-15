<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserFriend;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFriend>
 *
 * @method UserFriend|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFriend|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFriend[]    findAll()
 * @method UserFriend[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class UserFriendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFriend::class);
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

        if ($lastActivity) {
            $queryBuilder->andWhere('u.lastActivity > :lastActivity');
            $queryBuilder->setParameter('lastActivity', $lastActivity);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getFriend(User $user, User $friend): ?UserFriend
    {
        return $this->findOneBy([
            'user' => $user,
            'friend' => $friend,
        ]);
    }
}
