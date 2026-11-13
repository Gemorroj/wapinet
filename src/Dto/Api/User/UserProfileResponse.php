<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class UserProfileResponse
{
    public function __construct(
        public int $id,
        public string $username,
        public string $avatarUrl,
        public bool $isOnline,
        public ?string $sex,
        public ?string $birthday,
        public ?string $country,
        public ?string $countryName,
        public ?string $timezone,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $lastActivity,
        public ?string $vk,
        public ?string $info,
        public ?string $infoHtml,
        public bool $isFriend,
        public bool $canEdit,
        public UserCounters $counters,
        public ?VkProfile $vkProfile,
    ) {
    }
}
