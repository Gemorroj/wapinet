<?php
namespace App\Helper;

use M3uParser\M3uData;
use M3uParser\M3uParser;
use M3uParser\Tag\ExtInf;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Playlist хэлпер
 */
class Playlist
{
    /**
     * @var M3uParser
     */
    protected $m3uParser;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->m3uParser = new M3uParser();
        $this->m3uParser->addTag(ExtInf::class);
    }


    /**
     * @param File $file
     * @return M3uData
     */
    public function parseFile(File $file)
    {
        return $this->m3uParser->parseFile($file->getPathname());
    }
}
