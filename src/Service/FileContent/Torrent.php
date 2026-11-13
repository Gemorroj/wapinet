<?php

declare(strict_types=1);

namespace App\Service\FileContent;

use BitTorrent\Decoder;
use Symfony\Component\HttpFoundation\File\File;

final readonly class Torrent
{
    private Decoder $decoder;

    public function __construct()
    {
        $this->decoder = new Decoder();
    }

    public function decodeFile(File $file): array
    {
        return $this->decoder->decodeFile($file->getPathname())->getValues();
    }
}
