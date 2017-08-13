<?php
namespace WapinetBundle\Entity;

use Doctrine\ORM\EntityRepository;

class GuestbookRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function countAll()
    {
        return (int)$this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM WapinetBundle\Entity\Guestbook g')->getSingleScalarResult();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery()
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM WapinetBundle\Entity\Guestbook g ORDER BY g.id DESC');
    }
}
