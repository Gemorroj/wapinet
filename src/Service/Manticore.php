<?php

namespace App\Service;

use App\Pagerfanta\Manticore\Bridge;
use Doctrine\ORM\EntityManagerInterface;
use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Foolz\SphinxQL\SphinxQL;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Manticore
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private int $maxPerPage = 10;
    private int $page = 1;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;

        $this->connection = new Connection();
        $this->connection->setParams([
            'host' => $parameterBag->get('manticore_host'),
            'port' => $parameterBag->get('manticore_port'),
        ]);
        if ($parameterBag->has('wapinet_paginate_maxperpage')) {
            $this->maxPerPage = (int) $parameterBag->get('wapinet_paginate_maxperpage');
        }
    }

    public function select(int $page = 1): SphinxQL
    {
        $this->page = $page;

        return (new SphinxQL($this->connection))->select()->limit(
            ($this->page - 1) * $this->maxPerPage,
            $this->maxPerPage
        );
    }

    public function getPagerfanta(SphinxQL $sphinxQl, string $entityClass): Pagerfanta
    {
        $result = $sphinxQl->execute();
        // $sphinxQl->reset();
        $meta = $sphinxQl->query('SHOW META')->execute();

        $bridge = new Bridge($this->entityManager, $entityClass);
        $bridge->setManticoreResult($result, $meta);

        $pager = $bridge->getPager();
        $pager->setMaxPerPage($this->maxPerPage);
        $pager->setCurrentPage($this->page);

        return $pager;
    }
}
