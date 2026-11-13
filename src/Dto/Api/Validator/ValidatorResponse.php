<?php

declare(strict_types=1);

namespace App\Dto\Api\Validator;

final readonly class ValidatorResponse
{
    /**
     * @param list<ValidatorMessageResponse> $messages
     */
    public function __construct(
        public bool $valid,
        public array $messages,
        public ?string $source = null,
        public ?string $documentType = null,
        public ?string $encoding = null,
        public ?string $profile = null,
        public ?string $phpVersion = null,
    ) {
    }
}
