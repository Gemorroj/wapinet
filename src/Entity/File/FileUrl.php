<?php

namespace App\Entity\File;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUrl extends UploadedFile
{
    public function move($directory, string $name = null): File
    {
        $target = $this->getTargetFile($directory, $name);

        if (!@\rename($this->getPathname(), $target)) {
            $error = \error_get_last();
            throw new FileException(\sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, \strip_tags($error['message'])));
        }

        @\chmod($target, 0666 & ~\umask());

        return $target;
    }

    public function isValid(): bool
    {
        return \is_file($this->getPathname());
    }
}
