<?php
namespace App\Helper\Archiver;

use App\Exception\ArchiverException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

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
     * @return \SplFileObject[]
     */
    public function getFiles($directory)
    {
        $archiveDirectory = \basename($directory);
        ArchiveFileInfo::setArchiveDirectory($archiveDirectory);

        $objects = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        return $this->sortFiles($objects);
    }


    /**
     * @param \RecursiveIteratorIterator $objects
     * @return \SplFileObject[]
     */
    private function sortFiles(\RecursiveIteratorIterator $objects)
    {
        $result = [];
        $tmp = [];
        /** @var \SplFileObject $object */
		foreach ($objects as $name => $object) {
            $object->setInfoClass(ArchiveFileInfo::class);
            /** @var ArchiveFileInfo $info */
            $info = $object->getPathInfo();

            if (false === $object->isDir() && '' === $info->getArchiveName()) {
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
