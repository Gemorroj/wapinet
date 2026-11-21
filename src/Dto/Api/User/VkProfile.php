<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class VkProfile
{
    public function __construct(
        public int $id,
        public string $name,
        public string $photoUrl,
        public bool $isOnline,
    ) {
    }
}
