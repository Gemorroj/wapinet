<?php

namespace App\Service\Archiver;

final class ArchiveFileInfo extends \SplFileInfo
{
    public function getArchiveName(string $archive): string
    {
        $explodePath = \explode($archive, $this->getPathname(), 2);
        $path = \ltrim($explodePath[1], \DIRECTORY_SEPARATOR);
        $path = \str_replace('\\', '/', $path);

        return $path;
    }
}
