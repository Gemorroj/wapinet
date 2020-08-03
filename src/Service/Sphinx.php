<?php

namespace App\Service;

use App\Pagerfanta\Sphinx\Bridge;
use Doctrine\Persistence\ManagerRegistry;
use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Foolz\SphinxQL\SphinxQL;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Sphinx
{
    private ManagerRegistry $doctrine;
    private Connection $connection;
    private int $maxPerPage = 10;
    private int $page = 1;

    public function __construct(ManagerRegistry $doctrine, ParameterBagInterface $parameterBag)
    {
        $this->doctrine = $doctrine;

        $this->connection = new Connection();
        $this->connection->setParams([
            'host' => $parameterBag->get('sphinx_host'),
            'port' => $parameterBag->get('sphinx_port'),
            'charset' => 'utf8',
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
        $bridge = new Bridge($this->doctrine);
        $bridge->setRepositoryClass($entityClass);
        $bridge->setPkColumn('id');

        $result = $sphinxQl->execute();
        //$sphinxQl->reset();
        $meta = $sphinxQl->query('SHOW META')->execute();

        $bridge->setSphinxResult($result, $meta);

        $pager = $bridge->getPager();
        $pager->setMaxPerPage($this->maxPerPage);
        $pager->setCurrentPage($this->page);

        return $pager;
    }
}
