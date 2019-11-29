<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class EventRepository extends EntityRepository
{
    /**
     * @return Event[]
     */
    public function findNeedEmail(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.needEmail = 1')
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEventsQuery(User $user): Query
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.id', 'DESC')
            ->getQuery();
    }
}
