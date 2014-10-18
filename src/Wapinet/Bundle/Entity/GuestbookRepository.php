<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

class GuestbookRepository extends EntityRepository
{
    /**
     * @return number
     */
    public function countAll()
    {
        return (int)$this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM Wapinet\Bundle\Entity\Guestbook g')->getSingleScalarResult();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery()
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM Wapinet\Bundle\Entity\Guestbook g ORDER BY g.id DESC');
    }
}
