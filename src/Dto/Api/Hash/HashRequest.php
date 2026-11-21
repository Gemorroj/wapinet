<?php

declare(strict_types=1);

namespace App\Dto\Api\Hash;

use App\Service\Api\Hash\FileHasher;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class HashRequest
{
    /**
     * @param list<string> $algorithms
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 2048)]
        #[Assert\Url(protocols: ['http', 'https'], requireTld: false)]
        public string $url,
        #[Assert\Count(min: 1, max: 15)]
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [FileHasher::class, 'algorithmIds'], multiple: true)]
        public array $algorithms,
    ) {
    }
}
