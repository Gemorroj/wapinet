<?php

namespace App\Service;

use utils;
use Whois;

/**
 * Phpwhois хэлпер
 */
class Phpwhois
{
    public function getWhois(): Whois
    {
        return new Whois();
    }

    public function getUtils(): utils
    {
        return new utils();
    }
}
