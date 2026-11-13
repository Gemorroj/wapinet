<?php

declare(strict_types=1);

namespace App\Dto\Api\Guestbook;

use App\Dto\Api\User\UserSummary;

final readonly class GuestbookMessageResponse
{
    public function __construct(
        public int $id,
        public string $message,
        public string $messageHtml,
        public \DateTimeImmutable $createdAt,
        public ?UserSummary $user,
        public string $avatarUrl,
        public bool $isOnline,
        public ?string $ip,
        public ?string $browser,
        public ?string $countryCode,
        public ?string $countryName,
    ) {
    }
}
