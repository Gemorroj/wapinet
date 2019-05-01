<?php

namespace App\Helper;

use App\Pagerfanta\Sphinx\Bridge;
use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Foolz\SphinxQL\SphinxQL;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Sphinx хэлпер
 */
class Sphinx
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var int
     */
    private $maxPerPage;
    /**
     * @var int
     */
    private $page = 1;

    public function __construct(RegistryInterface $doctrine, ParameterBagInterface $parameterBag)
    {
        $this->doctrine = $doctrine;

        $this->connection = new Connection();
        $this->connection->setParams([
            'host' => $parameterBag->get('sphinx_host'),
            'port' => $parameterBag->get('sphinx_port'),
            'charset' => 'utf8',
        ]);
        $this->maxPerPage = $parameterBag->get('wapinet_paginate_maxperpage');
    }

    /**
     * @param int $page
     *
     * @return SphinxQL
     */
    public function select(int $page = 1): SphinxQL
    {
        $this->page = $page;

        $offset = ($this->page - 1) * $this->maxPerPage;
        $limit = $this->maxPerPage;

        return (new SphinxQL($this->connection))->select()->limit($offset, $limit);
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
