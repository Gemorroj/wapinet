<?php

declare(strict_types=1);

namespace App\Dto\Api\Home;

final readonly class HomeResponse
{
    public function __construct(
        public int $files,
        public int $gists,
        public int $guestbook,
        public int $online,
        public ?\DateTimeImmutable $newsLastDate,
        public ?int $forumTopics,
        public ?int $forumPosts,
    ) {
    }
}
