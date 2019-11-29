<?php

namespace App\Helper\Archiver;

use App\Exception\ArchiverException;
use Archive7z\Archive7z as Base7zArchive;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Archive7z хэлпер
 */
class Archive7z extends Archive
{
    /**
     * @param string $directory
     *
     * @throws ArchiverException
     *
     * @return File
     */
    public function create($directory)
    {
        throw new ArchiverException('Создание 7z архивов не поддерживается');
    }

    /**
     * @throws ArchiverException
     *
     * @return bool
     */
    public function isValid(File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));

        return $archive7z->isValid();
    }

    /**
     * @param string $directory
     *
     * @throws ArchiverException
     */
    public function extract($directory, File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));
        $archive7z->setOutputDirectory($directory);
        $archive7z->extract();
    }

    /**
     * @throws ArchiverException
     *
     * @return \Archive7z\Entry[]
     */
    public function getEntries(File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));

        return $archive7z->getEntries();
    }

    /**
     * @param string $entry
     * @param string $directory
     *
     * @throws ArchiverException
     */
    public function extractEntry(File $file, $entry, $directory)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));
        $archive7z->setOutputDirectory($directory);
        $archive7z->extractEntry($entry);
    }
}
