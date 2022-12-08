<?php

namespace App\Service;

use App\Pagerfanta\FixedPaginate;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Doctrine\Collections\CollectionAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Paginate
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    /**
     * @throws \RuntimeException
     */
    public function paginate(Query|QueryBuilder|Collection|array|FixedPaginate $data, int $page = 1, ?int $maxPerPage = null): Pagerfanta
    {
        if ($data instanceof Collection) {
            $adapter = new CollectionAdapter($data);
        } elseif ($data instanceof Query || $data instanceof QueryBuilder) {
            $adapter = new QueryAdapter($data, false);
        } elseif (\is_array($data)) {
            $adapter = new ArrayAdapter($data);
        } elseif ($data instanceof FixedPaginate) {
            $adapter = new FixedAdapter($data->getNbResults(), $data->getResults());
        } else {
            throw new \RuntimeException('Неизвестный тип данных для постраничной навигации');
        }

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage ?? $this->parameterBag->get('wapinet_paginate_maxperpage'));
        $pagerfanta->setCurrentPage(
            $this->normalizePage($pagerfanta, $page)
        );

        return $pagerfanta;
    }

    protected function normalizePage(Pagerfanta $pagerfanta, int $page): int
    {
        $maxPage = $pagerfanta->getNbPages();
        $minPage = 1;
        $currentPage = \max($page, $minPage);

        return \min($currentPage, $maxPage);
    }
}
