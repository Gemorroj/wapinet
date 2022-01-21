<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Size extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_size', [$this, 'getSize']),
        ];
    }

    public function getSize(int|float|null $fileSizeInBytes): string
    {
        if (null === $fileSizeInBytes) {
            return '';
        }

        $i = -1;
        $byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
        do {
            $fileSizeInBytes /= 1024;
            ++$i;
        } while ($fileSizeInBytes > 1024);

        return \round(\max($fileSizeInBytes, 0.1), 1).$byteUnits[$i];
    }
}
