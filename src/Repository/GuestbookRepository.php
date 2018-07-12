<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class GuestbookRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function countAll(): int
    {
        return $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM App\Entity\Guestbook g')->getSingleScalarResult();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery(): \Doctrine\ORM\Query
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\Guestbook g ORDER BY g.id DESC');
    }
}
