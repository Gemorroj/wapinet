<?php
namespace Wapinet\Bundle\Helper;

use Highco\SphinxBundle\Client\DefaultClient;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Sphinx хэлпер
 */
class Sphinx extends DefaultClient
{
    /**
     * @var ContainerInterface
     */
    protected $container;
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
        parent::__construct();

        $this->container = $container;
        $this->maxPerPage = $this->container->getParameter('wapinet_paginate_maxperpage');

        $this->SetMatchMode(SPH_MATCH_ANY);
    }


    /**
     * @param int $page
     *
     * @return Sphinx
     */
    public function setPage($page = 1)
    {
        $this->page = $page;

        $this->SetLimits(($this->page - 1) * $this->maxPerPage, $this->maxPerPage);

        return $this;
    }


    /**
     * @param array $result
     * @param string $entityClass
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getPagerfanta(array $result, $entityClass)
    {
        $bridge = $this->container->get('highco.sphinx.pager.white_october.doctrine_orm');
        $bridge->setRepositoryClass($entityClass);
        $bridge->setPkColumn('id');

        $bridge->setSphinxResults($result, true);

        $pager = $bridge->getPager();
        $pager->setMaxPerPage($this->maxPerPage);
        $pager->setCurrentPage($this->page);

        return $pager;
    }
}
