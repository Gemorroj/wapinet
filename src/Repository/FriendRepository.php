<?php
namespace App\Repository;

use App\Entity\Friend;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

class FriendRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return Query
     */
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

    /**
     * @param User $user
     * @param  \DateTime|null $lastActivity
     * @return int
     */
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

    /**
     * @param User $user
     * @param User $friend
     * @return null|Friend
     */
    public function getFriend(User $user, User $friend): ?Friend
    {
        return $this->findOneBy([
            'user' => $user,
            'friend' => $friend,
        ]);
    }
}
