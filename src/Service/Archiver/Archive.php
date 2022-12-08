<?php

namespace App\Service\Archiver;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

abstract class Archive
{
    public function __construct(protected ParameterBagInterface $parameterBag)
    {
    }

    protected function getTmpArchive(string $directory): string
    {
        return $directory.'.tmp';
    }

    /**
     * @return \SplFileObject[]
     */
    public function getFiles(string $directory): array
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
    private function sortFiles(\RecursiveIteratorIterator $objects): array
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

    abstract public function create(string $directory): File;

    abstract public function isValid(File $file): bool;

    abstract public function extract(string $directory, File $file): void;

    abstract public function extractEntry(File $file, string $entry, string $directory): void;
}
