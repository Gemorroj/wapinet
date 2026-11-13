<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

final readonly class WhoisContact
{
    public function __construct(
        public string $role,
        public ?string $name,
        public ?string $organization,
        public ?string $email,
    ) {
    }
}

