<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mime\MimeTypes;

class MimeGuesser
{
    private MimeTypes $mimeTypes;

    public function __construct()
    {
        $this->mimeTypes = MimeTypes::getDefault();
    }

    public function getMimeType(\SplFileInfo $file): string
    {
        $guessedMime = $this->mimeTypes->guessMimeType($file->getPathname());
        if (!$guessedMime) {
            $mime = $this->mimeTypes->getMimeTypes($file->getExtension());
            if (!$mime) {
                return 'application/octet-stream';
            }

            return $mime[0];
        }

        if ('application/octet-stream' === $guessedMime) {
            $mime = $this->mimeTypes->getMimeTypes($file->getExtension());
            if (!$mime) {
                return 'application/octet-stream';
            }

            return $mime[0];
        }

        if ('text/plain' === $guessedMime) {
            $mime = $this->mimeTypes->getMimeTypes($file->getExtension());
            if (!$mime) {
                return 'text/plain';
            }

            return $mime[0];
        }

        return $guessedMime;
    }
}
