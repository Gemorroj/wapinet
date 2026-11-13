<?php

declare(strict_types=1);

namespace App\Dto\Api\Http;

final readonly class HttpCallResponse
{
    /**
     * @param list<HttpCallHeader> $headers
     */
    public function __construct(
        public int $status,
        public string $statusText,
        public ?string $httpVersion,
        public string $url,
        public ?string $location,
        public float $time,
        public int $size,
        public bool $truncated,
        public ?string $contentType,
        public ?string $bodyLanguage,
        public bool $binary,
        public ?string $body,
        public ?string $bodyBase64,
        public array $headers,
    ) {
    }
}

