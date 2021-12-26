<?php

namespace App\Service;

use Ginfo\Info;

/**
 * Ginfo хэлпер
 */
class Ginfo
{
    private Info $info;

    /**
     * Ginfo constructor.
     *
     * @throws \Ginfo\Exceptions\FatalException
     */
    public function __construct()
    {
        $this->info = (new \Ginfo\Ginfo())->getInfo();
    }

    public function getInfo(): Info
    {
        return $this->info;
    }
}
