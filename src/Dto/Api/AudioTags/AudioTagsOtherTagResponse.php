<?php

declare(strict_types=1);

namespace App\Dto\Api\AudioTags;

final readonly class AudioTagsOtherTagResponse
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
    }
}
