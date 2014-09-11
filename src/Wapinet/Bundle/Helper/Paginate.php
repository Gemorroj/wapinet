<?php

namespace Wapinet\Bundle\Helper;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\Bundle\Pagerfanta\FixedPaginate;


class Paginate
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Query|QueryBuilder|Collection|array|FixedPaginate $data
     * @param int $page
     * @param int|null $maxPerPage
     *
     * @throws \RuntimeException
     * @return Pagerfanta
     */
    public function paginate($data, $page = 1, $maxPerPage = null)
    {
        if ($data instanceof Collection) {
            $adapter = new DoctrineCollectionAdapter($data);
        } elseif ($data instanceof Query || $data instanceof QueryBuilder) {
            $adapter = new DoctrineORMAdapter($data, false);
        } elseif (is_array($data)) {
            $adapter = new ArrayAdapter($data);
        } elseif ($data instanceof FixedPaginate) {
            $adapter = new FixedAdapter($data->getNbResults(), $data->getResults());
        } else {
            throw new \RuntimeException('Неизвестный тип данных для постраничной навигации');
        }

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(null === $maxPerPage ? $this->container->getParameter('wapinet_paginate_maxperpage') : $maxPerPage);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
