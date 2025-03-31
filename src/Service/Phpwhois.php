<?php

declare(strict_types=1);

namespace App\Service;

use PHPWhois2\Whois;

final readonly class Phpwhois
{
    public function getWhois(): Whois
    {
        return new Whois();
    }
}
