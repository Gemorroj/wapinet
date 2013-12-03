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

        if (false === $zip->setArchiveComment($this->container->getParameter('wapinet_archiver_comment'))) {
            throw new ArchiverException('Не удалось добавить комментарий к ZIP архиву');
        }

        $cd = getcwd();
        if (false === $cd) {
            throw new ArchiverException('Не удалось получить рабочий каталог');
        }
        if (false === chdir($directory)) {
            throw new ArchiverException('Не удалось изменить рабочий каталог');
        }
        if (false === $zip->addGlob('*', GLOB_NOSORT, array('remove_all_path' => true))) {
            throw new ArchiverException('Не удалось добавить файлы в ZIP архив');
        }
        if (false === chdir($cd)) {
            throw new ArchiverException('Не удалось вернуть рабочий каталог');
        }

        if (false === $zip->close()) {
            throw new ArchiverException('Не удалось создать ZIP архив');
        }

        return new File($tmpArchive);
    }
}
