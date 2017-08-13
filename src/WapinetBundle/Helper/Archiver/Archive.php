<?php
namespace WapinetBundle\Helper\Archiver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use WapinetBundle\Exception\ArchiverException;

/**
 * Archive хэлпер
 */
abstract class Archive
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
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
        $archiveDirectory = \basename($directory);
        ArchiveFileInfo::setArchiveDirectory($archiveDirectory);

        $objects = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $result = $this->sortFiles($objects);

        return $result;
    }


    /**
     * @param \RecursiveIteratorIterator $objects
     * @return \SplFileInfo[]
     */
    private function sortFiles(\RecursiveIteratorIterator $objects)
    {
        $result = array();
        $tmp = array();
        foreach ($objects as $name => $object) {
            $object->setInfoClass(ArchiveFileInfo::class);

            if (false === $object->isDir() && '' === $object->getPathInfo()->getArchiveName()) {
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

    /**
     * @param File $file
     * @param string $entry
     * @param string $directory
     * @throws ArchiverException
     */
    abstract public function extractEntry(File $file, $entry, $directory);
}
