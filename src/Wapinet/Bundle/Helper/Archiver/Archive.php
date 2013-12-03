<?php
namespace Wapinet\Bundle\Helper\Archiver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Wapinet\Bundle\Exception\ArchiverException;

/**
 * Archive хэлпер
 */
abstract class Archive
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $directory
     * @return string
     */
    protected function getTmpArchive($directory)
    {
        return $directory . '.tmp';
    }

    /**
     * @param string $directory
     * @return \SplFileInfo[]
     */
    public function getFiles($directory)
    {
        $result = array();
        $archiveDirectory = basename($directory);
        \Wapinet\Bundle\Entity\ArchiveFileInfo::setArchiveDirectory($archiveDirectory);

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        /** @var \SplFileInfo $object */
        foreach ($objects as $object) {
            // пропускаем ссылки
            //if ($object->isFile() === true || $object->isDir() === true) {
                $object->setInfoClass('\Wapinet\Bundle\Entity\ArchiveFileInfo');
                $result[] = $object;
            //}
        }

        return $result;
    }

    /**
     * @param string $directory
     * @throws ArchiverException
     * @return File
     */
    abstract public function create ($directory);

    /**
     * @param File $file
     * @throws ArchiverException
     * @return bool
     */
    abstract public function isValid (File $file);

    /**
     * @param string $directory
     * @param File $file
     * @throws ArchiverException
     */
    abstract public function extract($directory, File $file);
}
