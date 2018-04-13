<?php
namespace App\Helper;

/**
 * Ginfo хэлпер
 */
class Ginfo
{
    /**
     * @var \Ginfo\Info
     */
    protected $info;

    /**
     * Ginfo constructor.
     * @throws \Ginfo\Exceptions\FatalException
     */
    public function __construct()
    {
        $this->info = (new \Ginfo\Ginfo())->getInfo();
    }

    /**
     * @return \Ginfo\Info
     */
    public function getInfo()
    {
        return $this->info;
    }
}
