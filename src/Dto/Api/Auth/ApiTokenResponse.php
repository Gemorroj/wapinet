<?php

namespace App\Dto\Api\Auth;

use App\Entity\ApiToken;

final readonly class ApiTokenResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $expiresAt,
        public ?\DateTimeImmutable $lastUsedAt,
        public bool $current,
    ) {
    }

    public static function fromToken(ApiToken $token, bool $current): self
    {
        $lastUsedAt = $token->getLastUsedAt();

        return new self(
            (int) $token->getId(),
            $token->getName(),
            \DateTimeImmutable::createFromInterface($token->getCreatedAt()),
            \DateTimeImmutable::createFromInterface($token->getExpiresAt()),
            null !== $lastUsedAt ? \DateTimeImmutable::createFromInterface($lastUsedAt) : null,
            $current,
        );
    }
}
