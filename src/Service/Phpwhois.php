<?php

namespace App\Service;

use Whois;
use WhoisUtils;

/**
 * Phpwhois хэлпер
 */
class Phpwhois
{
    public function getWhois(): Whois
    {
        return new Whois();
    }

    public function getUtils(): WhoisUtils
    {
        return new WhoisUtils();
    }
}
