<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

class NewsRepository extends EntityRepository
{
    public function getAllBuilder()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('news')
            ->from('WapinetBundle:News', 'news')
            ->orderBy('news.id', 'DESC')
            ->getQuery();
    }

    public function getLastDate()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('news.createdAt')
            ->from('WapinetBundle:News', 'news')
            ->orderBy('news.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
    }
}