<?php
namespace Wapinet\Bundle\Helper\Archiver;

use Symfony\Component\HttpFoundation\File\File;
use Wapinet\Bundle\Exception\ArchiverException;
use Archive7z\Archive7z as Base7zArchive;

/**
 * Archive7z хэлпер
 */
class Archive7z extends Archive
{
    /**
     * @param string $directory
     * @return File
     * @throws ArchiverException
     */
    public function create ($directory)
    {
        throw new ArchiverException('Создание 7z архивов не поддерживается');
    }


    /**
     * @param File $file
     * @return bool
     * @throws ArchiverException
     */
    public function isValid (File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->container->getParameter('wapinet_7z_path'));

        return $archive7z->isValid();
    }


    /**
     * @param string $directory
     * @param File $file
     * @throws ArchiverException
     */
    public function extract($directory, File $file)
    {
        $archive7z = new Base7zArchive($file->getPathname());
        $archive7z->setOutputDirectory($directory);
        $archive7z->extract();
    }
}
