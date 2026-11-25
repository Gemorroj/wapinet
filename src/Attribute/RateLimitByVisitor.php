<?php

declare(strict_types=1);

namespace App\Attribute;

use App\Service\Api\RateLimitKey;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Лимит по пользователю для авторизованных запросов и по IP для гостей.
 *
 * Действие контроллера должно объявить аргумент "#[CurrentUser] ?User $user".
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final class RateLimitByVisitor extends RateLimit
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
            key: static function (array $args, Request $request): string {
                if (!\array_key_exists('user', $args)) {
                    throw new \LogicException(\sprintf('The "%s" attribute requires a "#[CurrentUser] ?User $user" argument in the controller action.', self::class));
                }

                $user = $args['user'];

                if (null !== $user && !$user instanceof UserInterface) {
                    throw new \LogicException(\sprintf('The "$user" argument of the controller action must implement "%s".', UserInterface::class));
                }

                return RateLimitKey::visitor($request, $user);
            },
            tokens: $tokens,
            methods: $methods,
            exposeHeaders: $exposeHeaders,
            if: $if,
        );
    }
}
