<?php

declare(strict_types=1);

namespace App\Attribute;

use App\Entity\User;
use App\Service\Api\RateLimitKey;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpKernel\Attribute\RateLimit;

/**
 * Лимит по текущему пользователю.
 *
 * Действие контроллера должно объявить аргумент "#[CurrentUser] User $user".
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final class RateLimitByUser extends RateLimit
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
            key: static function (array $args): string {
                $user = $args['user'] ?? null;

                if (!$user instanceof User) {
                    throw new \LogicException(\sprintf('The "%s" attribute requires a "#[CurrentUser] User $user" argument in the controller action.', self::class));
                }

                return RateLimitKey::user($user);
            },
            tokens: $tokens,
            methods: $methods,
            exposeHeaders: $exposeHeaders,
            if: $if,
        );
    }
}
