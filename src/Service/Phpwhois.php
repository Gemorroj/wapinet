<?php

declare(strict_types=1);

namespace App\Service;

class Phpwhois
{
    public function getWhois(): \Whois
    {
        return new \Whois();
    }
}
