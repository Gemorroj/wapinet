<?php

namespace WapinetUploaderBundle\Entity;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * File or url
 */
class FileUrl extends UploadedFile
{
    /**
     * Moves the file to a new location.
     *
     * @param string $directory The destination folder
     * @param string $name      The new file name
     *
     * @return File A File object representing the new file
     *
     * @throws FileException if the target file could not be created
     *
     * @api
     */
    public function move($directory, $name = null)
    {
        $target = $this->getTargetFile($directory, $name);

        if (!@\rename($this->getPathname(), $target)) {
            $error = \error_get_last();
            throw new FileException(\sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, \strip_tags($error['message'])));
        }

        @\chmod($target, 0666 & ~\umask());

        return $target;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return \file_exists($this->getPathname());
    }
}
