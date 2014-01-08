<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

class GuestbookRepository extends EntityRepository
{

    public function countAll()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM Wapinet\Bundle\Entity\Guestbook g');
        return $q->getSingleScalarResult();
    }
}
