<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

class FileRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function countAll()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL');
        return $q->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countToday()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL AND f.createdAt > :date');
        $q->setParameter('date', new \DateTime('today'));
        return $q->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countYesterday()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL AND f.createdAt > :date_start AND f.createdAt < :date_end');
        $q->setParameter('date_start', new \DateTime('yesterday'));
        $q->setParameter('date_end', new \DateTime('today'));
        return $q->getSingleScalarResult();
    }

    /**
     * @param string $date
     * @return \Doctrine\ORM\Query
     */
    public function getListBuilder($date = null)
    {
        $q = $this->createQueryBuilder('f')
            ->where('f.password IS NULL')
            ->orderBy('f.id', 'DESC');

        switch ($date) {
            case 'today':
                $q->andWhere('f.createdAt > :date');
                $q->setParameter('date', new \DateTime('today'));
                break;

            case 'yesterday':
                $q->andWhere('f.createdAt > :date_start');
                $q->andWhere('f.createdAt < :date_end');
                $q->setParameter('date_start', new \DateTime('yesterday'));
                $q->setParameter('date_end', new \DateTime('today'));
                break;
        }

        return $q->getQuery();
    }
}
