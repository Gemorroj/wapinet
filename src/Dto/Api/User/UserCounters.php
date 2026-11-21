<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class UserCounters
{
    public function __construct(
        public int $files,
        public int $gists,
        public int $friends,
    ) {
    }
}
