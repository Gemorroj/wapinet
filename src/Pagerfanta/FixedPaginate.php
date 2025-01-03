<?php

declare(strict_types=1);

namespace App\Pagerfanta;

class FixedPaginate
{
    private int $nbResults;
    private array $results;

    public function __construct(int $nbResults, array $results)
    {
        $this->setNbResults($nbResults);
        $this->setResults($results);
    }

    public function setNbResults(int $nbResults): self
    {
        $this->nbResults = $nbResults;

        return $this;
    }

    public function getNbResults(): int
    {
        return $this->nbResults;
    }

    public function setResults(array $results): self
    {
        $this->results = $results;

        return $this;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
