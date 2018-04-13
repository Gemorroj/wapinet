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
     * @param int $nbResults
     *
     * @return FixedPaginate
     */
    public function setNbResults($nbResults)
    {
        $this->nbResults = $nbResults;

        return $this;
    }

    /**
     * @return int
     */
    public function getNbResults()
    {
        return $this->nbResults;
    }


    /**
     * @param array $results
     *
     * @return FixedPaginate
     */
    public function setResults(array $results)
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }
}
