<?php

declare(strict_types=1);

namespace App\Service;

use M3uParser\M3uData;
use M3uParser\M3uParser;
use M3uParser\Tag\ExtInf;
use Symfony\Component\HttpFoundation\File\File;

class Playlist
{
    private M3uParser $m3uParser;

    public function __construct()
    {
        $this->m3uParser = new M3uParser();
        $this->m3uParser->addTag(ExtInf::class);
    }

    public function parseFile(File $file): M3uData
    {
        return $this->m3uParser->parseFile($file->getPathname());
    }
}
