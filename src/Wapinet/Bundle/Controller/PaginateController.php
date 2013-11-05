<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class PaginateController extends Controller
{
    /**
     * @param \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder $query
     * @param int $page
     *
     * @return Pagerfanta
     */
    public function paginate($query, $page = 1)
    {
        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($query));
        $pagerfanta->setMaxPerPage($this->container->getParameter('wapinet_paginate_maxperpage'));
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
