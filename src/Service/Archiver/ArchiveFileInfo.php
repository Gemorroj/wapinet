<?php

namespace App\Service\Archiver;

final class ArchiveFileInfo extends \SplFileInfo
{
    private static string $archiveDirectory;

    public static function setArchiveDirectory(string $archiveDirectory): void
    {
        self::$archiveDirectory = $archiveDirectory;
    }

    public function getArchiveName(): string
    {
        $explodePath = \explode(self::$archiveDirectory, $this->getPathname(), 2);
        $path = \ltrim($explodePath[1], \DIRECTORY_SEPARATOR);
        $path = \str_replace('\\', '/', $path);

        return $path;
    }
}
