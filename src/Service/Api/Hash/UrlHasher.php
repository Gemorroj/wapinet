<?php

declare(strict_types=1);

namespace App\Service\Api\Hash;

use App\Dto\Api\Hash\HashResponse;
use App\Service\FileSystem\RemoteFileDownloader;

/**
 * Хэширование файла по ссылке: скачивание идёт через общий загрузчик,
 * поэтому лимиты, редиректы и защита от SSRF совпадают с остальными формами.
 */
final readonly class UrlHasher
{
    public function __construct(
        private RemoteFileDownloader $downloader,
        private FileHasher $hasher,
    ) {
    }

    /**
     * @param list<string> $algorithms
     */
    public function hash(string $url, array $algorithms): HashResponse
    {
        $file = $this->downloader->download($url);

        try {
            return new HashResponse($this->hasher->hash($file->getPathname(), $algorithms));
        } finally {
            @\unlink($file->getPathname());
        }
    }
}
