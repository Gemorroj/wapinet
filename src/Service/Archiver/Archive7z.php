<?php

namespace App\Service\Archiver;

use App\Exception\ArchiverException;
use Archive7z\Archive7z as Base7zArchive;
use Symfony\Component\HttpFoundation\File\File;

class Archive7z extends Archive
{
    public function create(string $directory): File
    {
        throw new ArchiverException('Создание 7z архивов не поддерживается');
    }

    public function isValid(File $file): bool
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));

        return $archive7z->isValid();
    }

    public function extract(string $directory, File $file): void
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));
        $archive7z->setOutputDirectory($directory);
        $archive7z->extract();
    }

    /**
     * @return \Archive7z\Entry[]
     */
    public function getEntries(File $file, ?int $limit = null): array
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));

        return $archive7z->getEntries(null, $limit);
    }

    public function extractEntry(File $file, string $entry, string $directory): void
    {
        $archive7z = new Base7zArchive($file->getPathname(), $this->parameterBag->get('wapinet_7z_path'));
        $archive7z->setOutputDirectory($directory);
        $archive7z->extractEntry($entry);
    }
}
