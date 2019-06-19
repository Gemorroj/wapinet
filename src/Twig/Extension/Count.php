<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Count extends AbstractExtension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_count', [$this, 'getCount']),
        ];
    }

    /**
     * @param float $count
     * @param int   $decimals
     *
     * @return string
     */
    public function getCount($count, int $decimals = 0): string
    {
        return \number_format($count, $decimals, ',', ' ');
    }
}
