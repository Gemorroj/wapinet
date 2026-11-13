<?php

declare(strict_types=1);

namespace App\Dto\Api\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class HttpCallRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 2048)]
        public string $url = '',
        #[Assert\Length(max: 32)]
        public string $method = 'GET',
        #[Assert\Length(max: 16384)]
        public ?string $headers = null,
        #[Assert\Length(max: 1048576)]
        public ?string $body = null,
        #[Assert\Choice(choices: ['text', 'base64'])]
        public string $bodyEncoding = 'text',
        #[Assert\Range(min: 1, max: 30)]
        public int $timeout = 10,
        #[Assert\Choice(choices: ['auto', '1.1', '2', '3'])]
        public string $httpVersion = 'auto',
        public bool $verifyTls = true,
    ) {
    }
}

