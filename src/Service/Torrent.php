<?php

namespace App\Service;

use BitTorrent\Decoder;
use BitTorrent\Encoder;
use Symfony\Component\HttpFoundation\File\File;

class Torrent
{
    private Decoder $decoder;

    public function __construct()
    {
        $this->decoder = new Decoder(new Encoder());
    }

    public function decodeFile(File $file): array
    {
        return $this->decoder->decodeFile($file->getPathname());
    }
}
