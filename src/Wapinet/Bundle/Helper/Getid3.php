<?php
namespace Wapinet\Bundle\Helper;

/**
 * Getid3 хэлпер
 */
class Getid3
{
    /**
     * @return \getID3
     */
    public function getId3()
    {
        $getid3 = new \getID3();
        $getid3->encoding = 'UTF-8';

        return $getid3;
    }

    /**
     * @return \getid3_writetags
     */
    public function getId3Writer()
    {
        if (!defined('GETID3_INCLUDEPATH')) {
            $this->getId3();
        }
        include_once(GETID3_INCLUDEPATH.'write.php');

        $writer = new \getid3_writetags();
        $writer->tag_encoding = 'UTF-8';

        return $writer;
    }
}
