<?php
namespace Wapinet\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class UserRepository extends EntityRepository
{
    /**
     * @return Query
     */
    public function getOnlineUsersQuery()
    {
        return $this->createQueryBuilder('u')
            ->where('u.enabled = 1')
            ->andWhere('u.locked = 0')
            ->andWhere('u.expired = 0')
            ->andWhere('u.lastActivity > :lastActivity')
            ->setParameter('lastActivity', new \DateTime('now -' . User::LIFETIME))
            ->orderBy('u.username', 'ASC')
            ->getQuery();
    }

    /**
     * @param string|null $search
     * @param bool       $useInfo
     * @param bool       $onlyOnline
     * @param array|null $sex
     * @param \DateTime  $createdAfter
     * @param \DateTime  $createdBefore
     * @return Query
     */
    public function getSearchUsersQuery($search = null, $useInfo = true, $onlyOnline = true, array $sex = null, \DateTime $createdAfter = null, \DateTime $createdBefore = null)
    {
        $q = $this->createQueryBuilder('u');

        if (null !== $search) {
            $search = '%' . addcslashes($search, '%_') . '%';

            $q->where('u.username LIKE :search');
            if (true === $useInfo) {
                $q->orWhere('u.info LIKE :search');
            }
            $q->setParameter('search', $search);
        }

        if (true === $onlyOnline) {
            $q->andWhere('u.lastActivity > :lastActivity');
            $q->setParameter('lastActivity', new \DateTime('now -' . User::LIFETIME));
        }

        if ($sex) {
            $q->andWhere('u.sex IN (:sex)');
            $q->setParameter('sex', $sex);
        }

        if (null !== $createdAfter) {
            $q->andWhere('u.createdAt >= :created_after');
            $q->setParameter('created_after', $createdAfter);
        }
        if (null !== $createdBefore) {
            $q->andWhere('u.createdAt <= :created_before');
            $q->setParameter('created_before', $createdBefore);
        }

        $q->andWhere('u.enabled = 1');
        $q->andWhere('u.locked = 0');
        $q->andWhere('u.expired = 0');

        $q->orderBy('u.lastActivity', 'DESC');
        $q->addOrderBy('u.username', 'ASC');

        return $q->getQuery();
    }
}