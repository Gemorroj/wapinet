<?php

namespace App\Service;

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

    public function decodeFile(File $file): array
    {
        return $this->decoder->decodeFile($file->getPathname());
    }
}