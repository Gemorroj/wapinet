<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;
use Wapinet\UserBundle\Entity\User;

class GistRepository extends EntityRepository
{
    /**
     * @return number
     */
    public function countAll()
    {
        return $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM Wapinet\Bundle\Entity\Gist g')->getSingleScalarResult();
    }

    /**
     * @param User $user
     *
     * @return number
     */
    public function count(User $user = null)
    {
        if (null !== $user) {
            $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM Wapinet\Bundle\Entity\Gist g WHERE g.user = :user');
            $q->setParameter('user', $user);
        } else {
            $q = $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM Wapinet\Bundle\Entity\Gist g');
        }

        return $q->getSingleScalarResult();
    }

    /**
     * @param int $id
     *
     * @return number
     */
    public function countComments($id)
    {
        return 0;
        $q = $this->getEntityManager()->createQuery('SELECT t.numComments FROM Wapinet\CommentBundle\Entity\Thread t WHERE t.id = :id');
        $q->setParameter('id', 'gist-' . $id);

        $result = $q->getOneOrNullResult();

        return (null !== $result ? $result['numComments'] : 0);
    }

    /**
     * @param User $user
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
