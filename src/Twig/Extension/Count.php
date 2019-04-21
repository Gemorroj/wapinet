<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function number_format;

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
     * @param int $count
     *
     * @return string
     */
    public function getCount($count): string
    {
        return number_format($count, 0, ',', ' ');
    }
}
