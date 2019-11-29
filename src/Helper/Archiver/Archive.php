<?php

namespace App\Helper\Archiver;

use App\Exception\ArchiverException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Archive хэлпер
 */
abstract class Archive
{
    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param string $directory
     *
     * @return string
     */
    protected function getTmpArchive($directory)
    {
        return $directory.'.tmp';
    }

    /**
     * @param string $directory
     *
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
     *
     * @throws ArchiverException
     *
     * @return File
     */
    abstract public function create($directory);

    /**
     * @throws ArchiverException
     *
     * @return bool
     */
    abstract public function isValid(File $file);

    /**
     * @param string $directory
     *
     * @throws ArchiverException
     */
    abstract public function extract($directory, File $file);

    /**
     * @param string $entry
     * @param string $directory
     *
     * @throws ArchiverException
     */
    abstract public function extractEntry(File $file, $entry, $directory);
}
