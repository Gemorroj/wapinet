<?php
namespace Wapinet\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class EventRepository extends EntityRepository
{
    /**
     * @return Event[]
     */
    public function findNeedEmail()
    {
        return $this->createQueryBuilder('e')
            ->where('e.needEmail = 1')
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
     * @param User $user
     * @return Query
     */
    public function findEventsQuery(User $user)
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.id', 'DESC')
            ->getQuery();
    }
}
