<?php

declare(strict_types=1);

namespace App\Dto\Api\Obfuscator;

use App\Service\Api\Obfuscator\ObfuscatorLevel;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ObfuscatorRequest
{
    public const int MAX_CODE_SIZE = 262144;

    public function __construct(
        #[Assert\Length(max: self::MAX_CODE_SIZE)]
        public ?string $code = null,
        #[Assert\Length(max: 2048)]
        #[Assert\Url(protocols: ['http', 'https'], requireTld: false)]
        public ?string $url = null,
        public bool $removeComments = true,
        public ObfuscatorLevel $level = ObfuscatorLevel::Max,
    ) {
    }
}
