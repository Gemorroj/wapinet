<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class SubscriberResponse
{
    public function __construct(
        public bool $emailNews,
        public bool $emailFriends,
    ) {
    }
}
