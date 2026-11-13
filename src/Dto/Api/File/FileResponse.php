<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

use App\Dto\Api\User\UserSummary;

final readonly class FileResponse
{
    /**
     * @param list<string>              $tags
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public string $descriptionHtml,
        public int $size,
        public string $mimeType,
        public string $category,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $lastViewAt,
        public int $countViews,
        public ?UserSummary $user,
        public bool $hasPassword,
        public bool $hidden,
        public string $hash,
        public array $tags,
        public ?array $meta,
        public bool $canEdit,
        public bool $canDelete,
        public FileLinks $links,
        public FileNavigation $navigation,
        public FileContent $content,
    ) {
    }
}
