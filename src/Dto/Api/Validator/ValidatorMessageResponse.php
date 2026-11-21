<?php

declare(strict_types=1);

namespace App\Dto\Api\Validator;

final readonly class ValidatorMessageResponse
{
    public function __construct(
        public string $severity,
        public ?string $type,
        public ?int $line,
        public ?int $column,
        public string $message,
        public ?string $extract,
    ) {
    }
}
