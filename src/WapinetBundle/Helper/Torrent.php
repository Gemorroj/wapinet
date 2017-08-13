<?php
namespace WapinetBundle\Helper;

use PHP\BitTorrent\Decoder;
use PHP\BitTorrent\Encoder;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Torrent хэлпер
 */
class Torrent
{
    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->decoder = new Decoder(new Encoder());
    }


    /**
     * @param File $file
     * @return array
     */
    public function decodeFile(File $file)
    {
        return $this->decoder->decodeFile($file->getPathname());
    }
}
