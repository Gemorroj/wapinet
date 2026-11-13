<?php

namespace App\Service\FileContent\Backend;

final readonly class Ginfo
{
    private \Ginfo\Ginfo $ginfo;

    public function __construct()
    {
        $this->ginfo = new \Ginfo\Ginfo();
    }

    public function getGinfo(): \Ginfo\Ginfo
    {
        return $this->ginfo;
    }
}
