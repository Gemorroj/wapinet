<?php
namespace WapinetBundle\Entity;

use Doctrine\ORM\EntityRepository;
use WapinetUserBundle\Entity\User;

class GistRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function countAll()
    {
        return $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM WapinetBundle\Entity\Gist g')->getSingleScalarResult();
    }

    /**
     * @param User|null $user
     *
     * @return int
     */
    public function countUser(User $user = null)
    {
        if (null !== $user) {
            $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM WapinetBundle\Entity\Gist g WHERE g.user = :user');
            $q->setParameter('user', $user);
        } else {
            $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM WapinetBundle\Entity\Gist g');
        }

        return $q->getSingleScalarResult();
    }

    /**
     * @param User|null $user
     *
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery(User $user = null)
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