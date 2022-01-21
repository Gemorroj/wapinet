<?php

namespace App\Service;

use getid3_writetags;

class Getid3
{
    public function getId3(): \getID3
    {
        $getid3 = new \getID3();
        $getid3->encoding = 'UTF-8';

        return $getid3;
    }

    public function getId3Writer(): getid3_writetags
    {
        if (!\defined('GETID3_INCLUDEPATH')) {
            $this->getId3();
        }
        include_once GETID3_INCLUDEPATH.'write.php';

        $writer = new getid3_writetags();
        $writer->tag_encoding = 'UTF-8';

        return $writer;
    }
}
