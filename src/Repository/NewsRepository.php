<?php
namespace App\Repository;

use App\Entity\News;
use Doctrine\ORM\EntityRepository;

class NewsRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\Query
     */
    public function getAllBuilder()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('news')
            ->from(News::class, 'news')
            ->orderBy('news.id', 'DESC')
            ->getQuery();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getLastDate()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('news.createdAt')
            ->from(News::class, 'news')
            ->orderBy('news.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
    }
}
