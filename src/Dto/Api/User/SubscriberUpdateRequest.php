<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class SubscriberUpdateRequest
{
    public function __construct(
        public bool $emailNews = false,
        public bool $emailFriends = false,
    ) {
    }
}
