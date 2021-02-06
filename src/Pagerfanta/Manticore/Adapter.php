<?php

namespace App\Pagerfanta\Manticore;

use Pagerfanta\Adapter\AdapterInterface;

/**
 * Manticore Adapter.
 *
 * @author Stephane PY <py.stephane1@gmail.com>
 * @author Gemorroj <wapinet@mail.ru>
 */
class Adapter implements AdapterInterface
{
    protected array $array = [];
    protected int $nbResults = 0;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function getArray(): array
    {
        return $this->array;
    }

    public function setArray(array $array): self
    {
        $this->array = $array;

        return $this;
    }

    public function getNbResults(): int
    {
        return $this->nbResults;
    }

    public function setNbResults(int $v): self
    {
        $this->nbResults = $v;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        if ($offset >= \count($this->array)) {
            return $this->array;
        }

        return \array_slice($this->array, $offset, $length);
    }
}
