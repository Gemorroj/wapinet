<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Ключи счётчиков лимитов: IP, пользователь или гость (IP).
 */
final class RateLimitKey
{
    private function __construct()
    {
    }

    public static function ip(Request $request): string
    {
        return 'ip:'.($request->getClientIp() ?? 'unknown');
    }

    public static function user(User $user): string
    {
        return 'user:'.$user->getId();
    }

    public static function visitor(Request $request, ?UserInterface $user): string
    {
        return $user instanceof User ? self::user($user) : self::ip($request);
    }
}
