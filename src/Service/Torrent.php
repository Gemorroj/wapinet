<?php

declare(strict_types=1);

namespace App\Service;

use BitTorrent\Decoder;
use BitTorrent\Encoder;
use Symfony\Component\HttpFoundation\File\File;

final readonly class Torrent
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
