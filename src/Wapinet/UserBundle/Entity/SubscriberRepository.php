<?php
namespace Wapinet\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

class SubscriberRepository extends EntityRepository
{
    /**
     * @return Subscriber[]
     */
    public function findNeedEmail()
    {
        return $this->createQueryBuilder('s')
            ->where('s.needEmail = 1')
            ->getQuery()
            ->getResult();
    }
}
