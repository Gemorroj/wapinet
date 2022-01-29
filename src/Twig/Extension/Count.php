<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Count extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_count', [$this, 'getCount']),
        ];
    }

    public function getCount(?float $count, int $decimals = 0): string
    {
        if (null === $count) {
            return '';
        }

        return \number_format($count, $decimals, ',', ' ');
    }
}
