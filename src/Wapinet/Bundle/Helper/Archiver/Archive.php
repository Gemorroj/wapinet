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
        $archiveDirectory = basename($directory);
        \Wapinet\Bundle\Entity\ArchiveFileInfo::setArchiveDirectory($archiveDirectory);

        /** @var \SplFileInfo[] $objects */
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

        $result = $this->sortFiles($objects);

        return $result;
    }


    /**
     * @param \SplFileInfo[] $objects
     * @return \SplFileInfo[]
     */
    private function sortFiles(\Iterator $objects)
    {
        $result = array();
        $tmp = array();
        foreach ($objects as $name => $object) {
            $object->setInfoClass('\Wapinet\Bundle\Entity\ArchiveFileInfo');

            if ('' === $object->getPathInfo()->getArchiveName() && false === $object->isDir()) {
                $result[] = $object;
            } else {
                $tmp[$name] = $object;
            }
        }

        foreach ($tmp as $name => $object) {
            unset($tmp[$name]);
            $result[] = $object;
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
