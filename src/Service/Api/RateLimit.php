<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Проверка лимита в одном месте: 429 и Retry-After, без текста ошибки.
 * Человекочитаемое сообщение строит клиент по заголовку Retry-After.
 */
final class RateLimit
{
    private function __construct()
    {
    }

    public static function consumeForIp(RateLimiterFactoryInterface $limiter, Request $request): void
    {
        self::consume($limiter, self::ipKey($request));
    }

    public static function consumeForVisitor(RateLimiterFactoryInterface $limiter, Request $request, ?User $user): void
    {
        if (null !== $user) {
            self::consumeForUser($limiter, $user);

            return;
        }

        self::consumeForIp($limiter, $request);
    }

    public static function consumeForUser(RateLimiterFactoryInterface $limiter, User $user): void
    {
        self::consume($limiter, 'user:'.$user->getId());
    }

    private static function consume(RateLimiterFactoryInterface $limiter, string $key): void
    {
        $limit = $limiter->create($key)->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(\max(1, $limit->getRetryAfter()->getTimestamp() - \time()));
        }
    }

    private static function ipKey(Request $request): string
    {
        return 'ip:'.($request->getClientIp() ?? 'unknown');
    }
}
