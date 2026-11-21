<?php

declare(strict_types=1);

namespace App\Dto\Api\Validator;

final readonly class PhpValidatorInfoResponse
{
    public function __construct(
        public string $phpVersion,
    ) {
    }
}
