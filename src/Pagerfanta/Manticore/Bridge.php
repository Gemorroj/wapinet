<?php

namespace App\Pagerfanta\Manticore;

use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;

final class Bridge
{
    /**
     * @var int[]
     */
    private array $matchesPks = [];
    private int $totalFound = 0;
    private readonly string $pkColumn;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly string $entityClass)
    {
        $this->pkColumn = $this->entityManager->getClassMetadata($this->entityClass)->getSingleIdentifierColumnName();
    }

    public function setManticoreResult(array $result, int $totalFound): self
    {
        $this->totalFound = $totalFound;
        foreach ($result as $match) {
            $this->matchesPks[] = $match['id'];
        }

        return $this;
    }

    public function getPager(): Pagerfanta
    {
        $adapter = new Adapter($this->totalFound, $this->getResults());

        return new Pagerfanta($adapter);
    }

    private function getResults(): array
    {
        $results = [];
        if ($this->matchesPks) {
            $qb = $this->entityManager->createQueryBuilder();
            $q = $qb->select('r')
                ->from($this->entityClass, 'r INDEX BY r.'.$this->pkColumn)
                ->where($qb->expr()->in('r.'.$this->pkColumn, $this->matchesPks))
                ->getQuery();

            $unorderedResults = $q->getResult();
            foreach ($this->matchesPks as $pk) {
                if (isset($unorderedResults[$pk])) {
                    $results[$pk] = $unorderedResults[$pk];
                }
            }
        }

        return $results;
    }
}
