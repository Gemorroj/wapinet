<?php
namespace App\Entity;

use Doctrine\ORM\EntityRepository;

class GuestbookRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function countAll()
    {
        return (int)$this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM App\Entity\Guestbook g')->getSingleScalarResult();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery()
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\Guestbook g ORDER BY g.id DESC');
    }
}
