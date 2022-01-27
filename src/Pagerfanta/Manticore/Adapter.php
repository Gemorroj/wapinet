<?php

declare(strict_types=1);

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
    private array $array = [];
    private int $nbResults = 0;

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

    public function getSlice(int $offset, int $length): iterable
    {
        if ($offset >= \count($this->array)) {
            return $this->array;
        }

        return \array_slice($this->array, $offset, $length);
    }
}
