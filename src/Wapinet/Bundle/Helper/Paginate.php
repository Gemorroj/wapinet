<?php

namespace Wapinet\Bundle\Helper;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ContainerInterface;


class Paginate
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Конструктор
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Query|QueryBuilder|Collection $data
     * @param int $page
     *
     * @throws \RuntimeException
     * @return Pagerfanta
     */
    public function paginate($data, $page = 1)
    {
        if ($data instanceof Collection) {
            $adapter = new DoctrineCollectionAdapter($data);
        } elseif ($data instanceof Query || $data instanceof QueryBuilder) {
            $adapter = new DoctrineORMAdapter($data);
        } elseif (is_array($data)) {
            $adapter = new ArrayAdapter($data);
        } else {
            throw new \RuntimeException('Неизвестный тип данных для постраничной навигации');
        }

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($this->container->getParameter('wapinet_paginate_maxperpage'));
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
