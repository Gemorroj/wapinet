<?php

declare(strict_types=1);

namespace App\Service\Api\Archiver;

use App\Dto\Api\Archiver\ArchiveSourceRequest;
use App\Service\Api\File\RemoteFileFetcher;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ArchiveSourceResolver
{
    public function __construct(
        private RemoteFileFetcher $remoteFileFetcher,
    ) {
    }

    /**
     * @return non-empty-list<UploadedFile>
     */
    public function resolve(ArchiveSourceRequest $source): array
    {
        if ([] !== $source->files) {
            return $source->files;
        }

        return [$this->remoteFileFetcher->fetch((string) $source->url)];
    }
}
