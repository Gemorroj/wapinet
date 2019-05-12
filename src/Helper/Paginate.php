<?php

namespace App\Helper;

use App\Pagerfanta\FixedPaginate;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Paginate
{
    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param Query|QueryBuilder|Collection|array|FixedPaginate $data
     * @param int                                               $page
     * @param int|null                                          $maxPerPage
     *
     * @throws RuntimeException
     *
     * @return Pagerfanta
     */
    public function paginate($data, int $page = 1, ?int $maxPerPage = null): Pagerfanta
    {
        if ($data instanceof Collection) {
            $adapter = new DoctrineCollectionAdapter($data);
        } elseif ($data instanceof Query || $data instanceof QueryBuilder) {
            $adapter = new DoctrineORMAdapter($data, false);
        } elseif (\is_array($data)) {
            $adapter = new ArrayAdapter($data);
        } elseif ($data instanceof FixedPaginate) {
            $adapter = new FixedAdapter($data->getNbResults(), $data->getResults());
        } else {
            throw new RuntimeException('Неизвестный тип данных для постраничной навигации');
        }

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage ?? $this->parameterBag->get('wapinet_paginate_maxperpage'));
        $pagerfanta->setCurrentPage(
            $this->normalizePage($pagerfanta, $page)
        );

        return $pagerfanta;
    }

    /**
     * @param Pagerfanta $pagerfanta
     * @param int        $page
     *
     * @return int
     */
    protected function normalizePage(Pagerfanta $pagerfanta, int $page): int
    {
        $maxPage = $pagerfanta->getNbPages();
        $minPage = 1;
        $currentPage = $page < $minPage ? $minPage : $page;
        $currentPage = $currentPage > $maxPage ? $maxPage : $currentPage;

        return $currentPage;
    }
}
