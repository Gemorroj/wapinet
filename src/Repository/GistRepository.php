<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Gist;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

class GistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gist::class);
    }

    public function countUser(?User $user = null): int
    {
        if ($user) {
            return $this->count(['user' => $user]);
        }

        return $this->count([]);
    }

    public function getListQuery(?User $user = null): Query
    {
        $qb = $this->createQueryBuilder('g');
        if ($user) {
            $qb->where('g.user = :user');
            $qb->setParameter('user', $user);
        }
        $qb->orderBy('g.id', 'DESC');

        return $qb->getQuery();
    }
}
