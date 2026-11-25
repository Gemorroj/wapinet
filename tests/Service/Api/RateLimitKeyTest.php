<?php

declare(strict_types=1);

namespace App\Tests\Service\Api;

use App\Entity\User;
use App\Service\Api\RateLimitKey;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RateLimitKeyTest extends TestCase
{
    public function testIpKey(): void
    {
        self::assertSame('ip:127.0.0.1', RateLimitKey::ip($this->request()));
    }

    public function testIpKeyWithoutClientIp(): void
    {
        $request = Request::create('/');
        $request->server->remove('REMOTE_ADDR');

        self::assertSame('ip:unknown', RateLimitKey::ip($request));
    }

    public function testUserKey(): void
    {
        self::assertSame('user:42', RateLimitKey::user($this->user(42)));
    }

    public function testVisitorKeyUsesUserIdForAuthorizedVisitor(): void
    {
        self::assertSame('user:42', RateLimitKey::visitor($this->request(), $this->user(42)));
    }

    public function testVisitorKeyUsesIpForAnonymousVisitor(): void
    {
        self::assertSame('ip:127.0.0.1', RateLimitKey::visitor($this->request(), null));
    }

    private function request(): Request
    {
        return Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
    }

    private function user(int $id): User
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
