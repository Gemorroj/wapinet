<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    public function getAllBuilder(): Query
    {
        return $this->createQueryBuilder('news')
            ->orderBy('news.id', 'DESC')
            ->getQuery();
    }

    public function getLastNews(): ?News
    {
        return $this->createQueryBuilder('news')
            ->orderBy('news.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
