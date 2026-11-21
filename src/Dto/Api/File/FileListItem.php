<?php

namespace App\Dto\Api\File;

use App\Dto\Api\User\UserSummary;

final readonly class FileListItem
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public int $size,
        public string $mimeType,
        public string $category,
        public \DateTimeImmutable $createdAt,
        public ?UserSummary $user,
        public bool $hasPassword,
        public bool $hidden,
        public string $url,
        public ?string $thumbnailUrl,
        public ?array $meta,
    ) {
    }
}
