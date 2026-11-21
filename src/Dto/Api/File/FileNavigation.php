<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class FileNavigation
{
    public function __construct(
        public ?int $prevId,
        public ?int $nextId,
    ) {
    }
}
