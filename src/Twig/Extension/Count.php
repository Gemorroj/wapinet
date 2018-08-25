<?php

namespace App\Twig\Extension;

class Count extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('wapinet_count', [$this, 'getCount']),
        ];
    }

    /**
     * @param int $count
     *
     * @return string
     */
    public function getCount($count): string
    {
        return \number_format($count, 0, ',', ' ');
    }
}
