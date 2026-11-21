<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

final readonly class MimeGuesser
{
    private MimeTypes $mimeTypes;

    public function __construct()
    {
        $this->mimeTypes = MimeTypes::getDefault();
    }

    public function getMimeType(\SplFileInfo $file): string
    {
        $extension = \strtolower($this->extension($file));
        $splitArchiveMime = $this->splitArchiveMime($extension);

        if (null !== $splitArchiveMime) {
            return $splitArchiveMime;
        }

        $guessedMime = $this->mimeTypes->guessMimeType($file->getPathname());
        $guessedMime = null !== $guessedMime ? \strtolower($guessedMime) : null;

        if (!$guessedMime) {
            return $this->guessByExtension($file);
        }

        if (\in_array($guessedMime, ['application/octet-stream', 'text/plain', 'application/zip'], true)) {
            $mime = $this->mimeTypes->getMimeTypes($extension);

            return [] !== $mime ? \strtolower($mime[0]) : $guessedMime;
        }

        return $guessedMime;
    }

    private function guessByExtension(\SplFileInfo $file): string
    {
        $mime = $this->mimeTypes->getMimeTypes($this->extension($file));

        return [] !== $mime ? \strtolower($mime[0]) : 'application/octet-stream';
    }

    /**
     * Split archives are named `file.001`, `file.r00` or `file.z01`; the
     * content-based guesser cannot tell them apart from a random binary.
     */
    private function splitArchiveMime(string $extension): ?string
    {
        return match (true) {
            (bool) \preg_match('/^\d{3}$/', $extension) => 'application/x-7z-compressed',
            (bool) \preg_match('/^r\d{2}$/', $extension) => 'application/x-rar',
            (bool) \preg_match('/^z\d{2}$/', $extension) => 'application/zip',
            default => null,
        };
    }

    private function extension(\SplFileInfo $file): string
    {
        if ($file instanceof UploadedFile) {
            return \pathinfo($file->getClientOriginalName(), \PATHINFO_EXTENSION);
        }

        return $file->getExtension();
    }
}
