<?php
namespace Wapinet\Bundle\Helper;

use M3uParser\Entry;
use M3uParser\M3uParser;
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
    }


    /**
     * @param File $file
     * @return Entry[]
     */
    public function parseFile(File $file)
    {
        return $this->m3uParser->parseFile($file->getPathname());
    }
}
