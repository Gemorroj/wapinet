<?php

namespace App\Service\Archiver;

use App\Exception\ArchiverException;
use Symfony\Component\HttpFoundation\File\File;

class ArchiveRar extends Archive
{
    public function create(string $directory): File
    {
        throw new ArchiverException('Создание RAR архивов не поддерживается');
    }

    public function isValid(File $file): bool
    {
        $rar = \RarArchive::open($file->getPathname());
        if (false === $rar) {
            return false;
        }

        if (false === $rar->getEntries()) {
            return false;
        }

        if (false === $rar->close()) {
            throw new ArchiverException('Не удалось проверить RAR архив');
        }

        return true;
    }

    public function extract(string $directory, File $file): void
    {
        $rar = \RarArchive::open($file->getPathname());
        if (false === $rar) {
            throw new ArchiverException('Не удалось открыть RAR архив');
        }

        $entries = $rar->getEntries();
        if (false === $entries) {
            throw new ArchiverException('Не удалось получить объекты RAR архива');
        }

        foreach ($entries as $entry) {
            if (false === $entry->extract($directory)) {
                throw new ArchiverException('Не удалось распаковать объект RAR архива');
            }
        }

        if (false === $rar->close()) {
            throw new ArchiverException('Не удалось распаковать RAR архив');
        }
    }

    public function extractEntry(File $file, string $entry, string $directory): void
    {
        $rar = \RarArchive::open($file->getPathname());
        if (false === $rar) {
            throw new ArchiverException('Не удалось открыть RAR архив');
        }

        $rarEntry = $rar->getEntry($entry);
        if (false === $rarEntry) {
            throw new ArchiverException('Не удалось получить объект RAR архива');
        }

        if (false === $rarEntry->extract($directory)) {
            throw new ArchiverException('Не удалось распаковать объект RAR архива');
        }

        if (false === $rar->close()) {
            throw new ArchiverException('Не удалось распаковать RAR архив');
        }
    }
}
