<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class FilePermission
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $danger,
        public bool $warning,
        public bool $cost,
    ) {
    }
}
