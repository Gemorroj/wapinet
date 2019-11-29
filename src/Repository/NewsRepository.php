<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\ORM\EntityRepository;

class NewsRepository extends EntityRepository
{
    public function getAllBuilder(): \Doctrine\ORM\Query
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('news')
            ->from(News::class, 'news')
            ->orderBy('news.id', 'DESC')
            ->getQuery();
    }

    public function getLastDate(): \Doctrine\ORM\Query
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('news.createdAt')
            ->from(News::class, 'news')
            ->orderBy('news.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
    }
}
