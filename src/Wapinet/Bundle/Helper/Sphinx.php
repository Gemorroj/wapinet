<?php
namespace Wapinet\Bundle\Helper;

use Foolz\SphinxQL\Drivers\ResultSetInterface;
use Foolz\SphinxQL\SphinxQL;
use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\Bundle\Pagerfanta\Sphinx\Bridge;


/**
 * Sphinx хэлпер
 */
class Sphinx
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var int
     */
    protected $maxPerPage;
    /**
     * @var int
     */
    private $page = 1;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->connection = new Connection();
        $this->connection->setParams(array(
            'host' => $container->getParameter('sphinx_host'),
            'port' => $container->getParameter('sphinx_port'),
            'charset' => 'utf8',
        ));
        $this->maxPerPage = $container->getParameter('wapinet_paginate_maxperpage');

        $this->container = $container;
    }


    /**
     * @param int $page
     * @return SphinxQL
     */
    public function select($page = 1)
    {
        $offset = ($page - 1) * $this->maxPerPage;
        $limit = $this->maxPerPage;

        return SphinxQL::create($this->connection)->select()->limit($offset, $limit);
    }


    /**
     * @param ResultSetInterface $result
     * @param string $entityClass
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getPagerfanta(ResultSetInterface $result, $entityClass)
    {
        $bridge = new Bridge($this->container);
        $bridge->setRepositoryClass($entityClass);
        $bridge->setPkColumn('id');

        $bridge->setSphinxResult($result);

        $pager = $bridge->getPager();
        $pager->setMaxPerPage($this->maxPerPage);
        $pager->setCurrentPage($this->page);

        return $pager;
    }
}
