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
final readonly class Adapter implements AdapterInterface
{
    private array $results;
    private int $nbResults;

    public function __construct(int $nbResults, array $results)
    {
        $this->nbResults = $nbResults;
        $this->results = $results;
    }

    public function getNbResults(): int
    {
        return $this->nbResults;
    }

    public function getSlice(int $offset, int $length): iterable
    {
        if ($offset >= \count($this->results)) {
            return $this->results;
        }

        return \array_slice($this->results, $offset, $length);
    }
}
