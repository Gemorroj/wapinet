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
     * @return File
     *
     * @throws ArchiverException
     */
    public function create($directory)
    {
        throw new ArchiverException('Создание 7z архивов не поддерживается');
    }

    /**
     * @param File $file
     *
     * @return bool
     *
     * @throws ArchiverException
     */
    public function isValid(File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->container->getParameter('wapinet_7z_path'));

        return $archive7z->isValid();
    }

    /**
     * @param string $directory
     * @param File   $file
     *
     * @throws ArchiverException
     */
    public function extract($directory, File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->container->getParameter('wapinet_7z_path'));
        $archive7z->setOutputDirectory($directory);
        $archive7z->extract();
    }

    /**
     * @param File $file
     *
     * @throws ArchiverException
     *
     * @return \Archive7z\Entry[]
     */
    public function getEntries(File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->container->getParameter('wapinet_7z_path'));

        return $archive7z->getEntries();
    }

    /**
     * @param File   $file
     * @param string $entry
     * @param string $directory
     *
     * @throws ArchiverException
     */
    public function extractEntry(File $file, $entry, $directory)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->container->getParameter('wapinet_7z_path'));
        $archive7z->setOutputDirectory($directory);
        $archive7z->extractEntry($entry);
    }
}
