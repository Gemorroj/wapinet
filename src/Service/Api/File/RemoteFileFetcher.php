<?php

declare(strict_types=1);

namespace App\Service\Api\File;

use App\Entity\File\FileUrl;
use App\Exception\PublicHttpException;
use App\Service\FileSystem\RemoteFileDownloader;
use App\Service\FileSystem\RemoteFileException;
use App\Service\Network\OutboundUrlBlockedException;

/**
 * Downloads a file from a public HTTP(S) URL for upload-by-link.
 */
final readonly class RemoteFileFetcher
{
    public function __construct(
        private RemoteFileDownloader $downloader,
    ) {
    }

    public function fetch(string $url, ?int $maxSize = null): FileUrl
    {
        try {
            return $this->downloader->download($url, maxSize: $maxSize);
        } catch (OutboundUrlBlockedException|RemoteFileException $exception) {
            throw new PublicHttpException(422, $exception->getMessage(), $exception);
        }
    }
}
