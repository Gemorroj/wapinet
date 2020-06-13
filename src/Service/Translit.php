<?php

namespace App\Service;

/**
 * Translit хэлпер
 */
class Translit
{
    public function toAscii(string $str): string
    {
        return \transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove', $str);
    }
}
