<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

class FileRepository extends EntityRepository
{
    public function countAll()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL');
        return $q->getSingleScalarResult();
    }

    public function countToday()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL AND f.createdAt > :date');
        $q->setParameter('date', new \DateTime('today'));
        return $q->getSingleScalarResult();
    }

    public function countYesterday()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL AND f.createdAt > :date');
        $q->setParameter('date', new \DateTime('yesterday'));
        return $q->getSingleScalarResult();
    }
}
