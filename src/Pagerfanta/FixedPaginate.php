<?php

namespace App\Pagerfanta;

class FixedPaginate
{
    protected $nbResults;
    protected $results;

    /**
     * @param int   $nbResults
     * @param array $results
     */
    public function __construct($nbResults = null, array $results = null)
    {
        $this->setNbResults($nbResults);
        $this->setResults($results);
    }

    /**
     * @return FixedPaginate
     */
    public function setNbResults(int $nbResults): self
    {
        $this->nbResults = $nbResults;

        return $this;
    }

    public function getNbResults(): int
    {
        return $this->nbResults;
    }

    /**
     * @return FixedPaginate
     */
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
