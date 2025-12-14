<?php

namespace App\Service\Archiver;

use App\Exception\ArchiverException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

abstract class Archive
{
    private const int MAX_FILES = 1000;
    private const int MAX_FILE_SIZE = 50 * 1024 * 1024;

    public function __construct(protected ParameterBagInterface $parameterBag)
    {
    }

    protected function getTmpArchive(string $directory): string
    {
        return $directory.'.tmp';
    }

    protected function validateArchivePath(string $filename): void
    {
        $filename = \str_replace('\\', '/', $filename);

        if (\str_contains($filename, '../')) {
            throw new ArchiverException('Архив содержит недопустимые пути: '.$filename);
        }

        if (\str_starts_with($filename, '/')) {
            throw new ArchiverException('Архив содержит абсолютные пути: '.$filename);
        }
    }

    protected function validateFileSize(int $fileSize): void
    {
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new ArchiverException('Файл в архиве слишком большой: '.$fileSize.' байт');
        }
    }

    protected function validateFileCount(int $fileCount): void
    {
        if ($fileCount > self::MAX_FILES) {
            throw new ArchiverException('Архив содержит слишком много файлов: '.$fileCount);
        }
    }

    /**
     * @return \SplFileObject[]
     */
    public function getFiles(string $archiveDirectory): array
    {
        $objects = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($archiveDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $archive = \basename($archiveDirectory);
        $result = [];
        $tmp = [];
        /** @var \SplFileObject $object */
        foreach ($objects as $name => $object) {
            $object->setInfoClass(ArchiveFileInfo::class);
            /** @var ArchiveFileInfo $info */
            $info = $object->getPathInfo();

            if (false === $object->isDir() && '' === $info->getArchiveName($archive)) {
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
