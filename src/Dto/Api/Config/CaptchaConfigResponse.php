<?php

namespace App\Dto\Api\Config;

final readonly class CaptchaConfigResponse
{
    public function __construct(
        public bool $enabled,
        public string $siteKey,
    ) {
    }
}
