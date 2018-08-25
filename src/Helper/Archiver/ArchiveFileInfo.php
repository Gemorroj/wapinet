<?php

namespace App\Helper\Archiver;

/**
 * ArchiveFileInfo
 */
class ArchiveFileInfo extends \SplFileInfo
{
    /**
     * @var string
     */
    protected static $archiveDirectory;

    /**
     * @param string $archiveDirectory
     */
    public static function setArchiveDirectory(string $archiveDirectory): void
    {
        self::$archiveDirectory = $archiveDirectory;
    }

    /**
     * @return string
     */
    public function getArchiveName(): string
    {
        $explodePath = \explode(self::$archiveDirectory, $this->getPathname(), 2);
        $path = \ltrim($explodePath[1], \DIRECTORY_SEPARATOR);
        $path = \str_replace('\\', '/', $path);

        return $path;
    }
}
