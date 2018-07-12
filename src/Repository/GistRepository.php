<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class GistRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function countAll(): int
    {
        return $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM App\Entity\Gist g')->getSingleScalarResult();
    }

    /**
     * @param User|null $user
     *
     * @return int
     */
    public function countUser(?User $user = null): int
    {
        if (null !== $user) {
            $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM App\Entity\Gist g WHERE g.user = :user');
            $q->setParameter('user', $user);
        } else {
            $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM App\Entity\Gist g');
        }

        return $q->getSingleScalarResult();
    }

    /**
     * @param User|null $user
     *
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery(?User $user = null): \Doctrine\ORM\Query
    {
        $qb = $this->createQueryBuilder('g');
        if (null !== $user) {
            $qb->where('g.user = :user');
            $qb->setParameter('user', $user);
        }
        $qb->orderBy('g.id', 'DESC');

        return $qb->getQuery();
    }
}
