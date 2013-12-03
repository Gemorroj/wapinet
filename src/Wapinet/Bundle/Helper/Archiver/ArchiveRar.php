<?php
namespace Wapinet\Bundle\Helper\Archiver;

use Symfony\Component\HttpFoundation\File\File;
use Wapinet\Bundle\Exception\ArchiverException;

/**
 * ArchiveRar хэлпер
 */
class ArchiveRar extends Archive
{
    /**
     * @param string $directory
     * @return File
     * @throws ArchiverException
     */
    public function create ($directory)
    {
        throw new ArchiverException('Создание RAR архивов не поддерживается');
    }


    /**
     * @param File $file
     * @return bool
     * @throws ArchiverException
     */
    public function isValid (File $file)
    {
        $rar = @\RarArchive::open($file->getPathname());
        if (false === $rar) {
            return false;
        }

        if (false === $rar->close()) {
            throw new ArchiverException('Не удалось проверить RAR архив');
        }

        return true;
    }


    /**
     * @param string $directory
     * @param File $file
     * @throws ArchiverException
     */
    public function extract($directory, File $file)
    {
        $rar = \RarArchive::open($file->getPathname());
        if (false === $rar) {
            throw new ArchiverException('Не удалось открыть RAR архив');
        }

        $entries = $rar->getEntries();
        if (false === $rar) {
            throw new ArchiverException('Не удалось получить объекты RAR архива');
        }

        foreach ($entries as $entry) {
            if (false === $entry->extract($directory)) {
                throw new ArchiverException('Не удалось распаковать объект RAR архива');
            }
        }

        if (false === $rar->close()) {
            throw new ArchiverException('Не удалось распаковать RAR архив');
        }
    }
}
