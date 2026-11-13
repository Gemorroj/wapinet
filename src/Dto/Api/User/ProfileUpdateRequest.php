<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProfileUpdateRequest
{
    public function __construct(
        #[Assert\Choice(choices: ['m', 'f'])]
        public ?string $sex = null,
        #[Assert\Date]
        public ?string $birthday = null,
        #[Assert\Timezone]
        public ?string $timezone = null,
        #[Assert\Country]
        public ?string $country = null,
        #[Assert\Length(max: 255)]
        #[Assert\Regex(pattern: '/^[a-z0-9_]*$/')]
        public ?string $vk = null,
        #[Assert\Length(max: 5000)]
        public ?string $info = null,
    ) {
    }
}
