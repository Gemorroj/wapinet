<?php
namespace Wapinet\Bundle\Helper\Archiver;

use Symfony\Component\HttpFoundation\File\File;
use Wapinet\Bundle\Exception\ArchiverException;

/**
 * ArchiveZip хэлпер
 */
class ArchiveZip extends Archive
{
    /**
     * @param string $directory
     * @return File
     * @throws ArchiverException
     */
    public function create ($directory)
    {
        $tmpArchive = $this->getTmpArchive($directory);

        $zip = new \ZipArchive;
        $result = $zip->open($tmpArchive, \ZipArchive::CREATE | \ZIPARCHIVE::OVERWRITE);
        if (true !== $result) {
            throw new ArchiverException('Не удалось создать ZIP архив', $result);
        }

        if (false === $zip->addGlob($directory . '/*', GLOB_NOSORT, array('add_path' => '/', 'remove_all_path' => true))) {
            throw new ArchiverException('Не удалось добавить файлы в ZIP архив');
        }

        if (false === $zip->close()) {
            throw new ArchiverException('Не удалось создать ZIP архив');
        }

        return new File($tmpArchive);
    }
}
