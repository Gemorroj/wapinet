<?php
namespace Wapinet\Bundle\Helper;


/**
 * Translit хэлпер
 */
class Translit
{
    public function toAscii($str)
    {
        return iconv('UTF-8', 'US-ASCII//TRANSLIT', $str);
    }
}
