<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

class GovnokodRepository extends EntityRepository
{

    public function countAll()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM Wapinet\Bundle\Entity\Govnokod g');
        return $q->getSingleScalarResult();
    }

    public function getListQuery()
    {
        $q = $this->getEntityManager()->createQuery('SELECT g FROM Wapinet\Bundle\Entity\Govnokod g ORDER BY g.id DESC');
        return $q;
    }
}
