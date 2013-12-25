<?php
namespace Wapinet\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class SubscriberRepository extends EntityRepository
{
    /**
     * @return Subscriber[]
     */
    public function findNeedEmail()
    {
        return $this->createQueryBuilder('s')
            ->where('s.needEmail = 1')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
     * @param User $user
     * @return Query
     */
    public function findEventsQuery(User $user)
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.id', 'DESC')
            ->getQuery();
    }
}
