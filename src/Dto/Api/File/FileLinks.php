<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class FileLinks
{
    public function __construct(
        public string $url,
        public string $downloadUrl,
        public ?string $thumbnailUrl,
        public ?string $convertedUrl,
        public ?string $convertedFormat,
    ) {
    }
}
