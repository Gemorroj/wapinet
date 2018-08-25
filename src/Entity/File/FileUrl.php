<?php

namespace App\Entity\File;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * File or url.
 */
class FileUrl extends UploadedFile
{
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isValid()
    {
        return \file_exists($this->getPathname());
    }
}
