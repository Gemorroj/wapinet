<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

class TagRepository extends EntityRepository
{
    /**
     * @param string $search
     * @param int $limit
     * @return Tag[]
     */
    public function findLikeName($search, $limit = 10)
    {
        $qb = $this->createQueryBuilder('t');

        $search = '%' . addcslashes($search, '%_') . '%';

        $qb->where('t.name LIKE :search');
        $qb->setParameter('search', $search);

        $qb->orderBy('t.count', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->execute();
    }
}
