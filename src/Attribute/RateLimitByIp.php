<?php

declare(strict_types=1);

namespace App\Attribute;

use App\Service\Api\RateLimitKey;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\RateLimit;

/**
 * Лимит по IP клиента (общий для всех действий с одним лимитером).
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final class RateLimitByIp extends RateLimit
{
    public function __construct(
        string $limiter,
        int $tokens = 1,
        array|string $methods = [],
        bool $exposeHeaders = false,
        bool|Expression|\Closure $if = true,
    ) {
        parent::__construct(
            $limiter,
            key: static fn (array $args, Request $request): string => RateLimitKey::ip($request),
            tokens: $tokens,
            methods: $methods,
            exposeHeaders: $exposeHeaders,
            if: $if,
        );
    }
}
