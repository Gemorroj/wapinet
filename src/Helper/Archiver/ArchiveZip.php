<?php

namespace App\Helper\Archiver;

use App\Exception\ArchiverException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * ArchiveZip хэлпер
 */
class ArchiveZip extends Archive
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
        $tmpArchive = $this->getTmpArchive($directory);

        $zip = new \ZipArchive();
        $result = $zip->open($tmpArchive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if (true !== $result) {
            throw new ArchiverException('Не удалось создать ZIP архив', $result);
        }

        if (false === $zip->setArchiveComment($this->container->getParameter('wapinet_archiver_comment'))) {
            throw new ArchiverException('Не удалось добавить комментарий к ZIP архиву');
        }

        foreach ($this->getFiles($directory) as $entry) {
            /** @var ArchiveFileInfo $info */
            $info = $entry->getPathInfo();
            $dir = $info->getArchiveName();
            $dir = ('' !== $dir ? $dir.'/' : '');

            if (true === $entry->isDir()) {
                if (false === $zip->addEmptyDir($dir.$entry->getFilename())) {
                    throw new ArchiverException('Не удалось добавить директорию в ZIP архив');
                }
            } else {
                if (false === $zip->addFile($entry->getPathname(), $dir.$entry->getFilename())) {
                    throw new ArchiverException('Не удалось добавить файл в ZIP архив');
                }
            }
        }

        if (false === $zip->close()) {
            throw new ArchiverException('Не удалось создать ZIP архив');
        }

        return new File($tmpArchive);
    }

    /**
     * @param File $file
     *
     * @throws ArchiverException
     *
     * @return bool
     */
    public function isValid(File $file)
    {
        $zip = new \ZipArchive();
        $result = $zip->open($file->getPathname(), \ZipArchive::CHECKCONS);
        if (true !== $result) {
            return false;
        }

        if (false === $zip->close()) {
            throw new ArchiverException('Не удалось проверить ZIP архив');
        }

        return true;
    }

    /**
     * @param string $directory
     * @param File   $file
     *
     * @throws ArchiverException
     */
    public function extract($directory, File $file)
    {
        $zip = new \ZipArchive();
        $result = $zip->open($file->getPathname());
        if (true !== $result) {
            throw new ArchiverException('Не удалось открыть ZIP архив', $result);
        }

        if (true !== $zip->extractTo($directory)) {
            throw new ArchiverException('Не удалось распаковать ZIP архив');
        }
        if (false === $zip->close()) {
            throw new ArchiverException('Не удалось распаковать ZIP архив');
        }
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
        $zip = new \ZipArchive();
        $result = $zip->open($file->getPathname());
        if (true !== $result) {
            throw new ArchiverException('Не удалось открыть ZIP архив', $result);
        }

        if (true !== $zip->extractTo($directory, $entry)) {
            throw new ArchiverException('Не удалось распаковать ZIP архив');
        }
        if (false === $zip->close()) {
            throw new ArchiverException('Не удалось распаковать ZIP архив');
        }
    }
}
