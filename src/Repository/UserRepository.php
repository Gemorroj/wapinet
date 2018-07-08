<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class UserRepository extends EntityRepository
{
    /**
     * @return Query
     */
    public function getOnlineUsersQuery()
    {
        return $this->createQueryBuilder('u')
            ->where('u.enabled = 1')
            ->andWhere('u.lastActivity > :lastActivity')
            ->setParameter('lastActivity', new \DateTime('now -' . User::LIFETIME))
            ->orderBy('u.username', 'ASC')
            ->getQuery();
    }
}
