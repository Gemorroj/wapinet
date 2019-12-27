<?php

namespace App\Repository;

use App\Entity\Guestbook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GuestbookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guestbook::class);
    }

    public function countAll(): int
    {
        return $this->getEntityManager()->createQuery('SELECT COUNT(g.id) FROM App\Entity\Guestbook g')->getSingleScalarResult();
    }

    public function getListQuery(): \Doctrine\ORM\Query
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\Guestbook g ORDER BY g.id DESC');
    }
}
