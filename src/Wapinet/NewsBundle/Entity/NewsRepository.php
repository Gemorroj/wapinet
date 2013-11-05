<?php
namespace Wapinet\NewsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class NewsRepository extends EntityRepository
{
    public function getPages($page = 1)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('news')
            ->from('WapinetNewsBundle:News', 'news')
            ->orderBy('news.id', 'DESC');

        $adapter = new DoctrineORMAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(2);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}