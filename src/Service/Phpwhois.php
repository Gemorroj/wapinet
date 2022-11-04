<?php

namespace App\Service;

class Phpwhois
{
    public function getWhois(): \Whois
    {
        return new \Whois();
    }

    public function getUtils(): \WhoisUtils
    {
        return new \WhoisUtils();
    }
}
