<?php
namespace Wapinet\Bundle\Helper;

/**
 * Translit хэлпер
 */
class Translit
{
    /**
     * @param string $str
     * @return string
     */
    public function toAscii($str)
    {
        return transliterator_transliterate('Any-Latin; Latin-ASCII;', $str);
    }
}