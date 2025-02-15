<?php

namespace App\Service;

use Ginfo\Info;

final readonly class Ginfo
{
    private Info $info;

    /**
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
