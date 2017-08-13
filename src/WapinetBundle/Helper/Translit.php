<?php
namespace WapinetBundle\Helper;

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
        // return \transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $str);
        return \iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    }
}
