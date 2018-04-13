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
    public static function setArchiveDirectory($archiveDirectory)
    {
        self::$archiveDirectory = $archiveDirectory;
    }

    /**
     * @return string
     */
    public function getArchiveName()
    {
        $explodePath = \explode(self::$archiveDirectory, $this->getPathname(), 2);
        $path = \ltrim($explodePath[1], \DIRECTORY_SEPARATOR);
        $path = \str_replace('\\', '/', $path);

        return $path;
    }
}
