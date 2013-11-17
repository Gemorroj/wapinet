<?php
namespace Wapinet\Bundle\Helper;

/**
 * Getid3 хэлпер
 */
class Getid3
{
    public function getId3()
    {
        return new \getID3();
    }

    public function getId3Writer()
    {
        return new \getid3_writetags();
    }
}
