<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Base64 extends AbstractExtension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters(): array
    {
        return [
            new TwigFunction('base64_encode', 'base64_encode'),
            new TwigFunction('base64_decode', 'base64_decode'),
        ];
    }
}
