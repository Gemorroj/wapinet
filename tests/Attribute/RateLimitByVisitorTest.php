<?php

declare(strict_types=1);

namespace App\Tests\Attribute;

use App\Attribute\RateLimitByVisitor;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RateLimitByVisitorTest extends TestCase
{
    public function testKeyIsBuiltFromTheUser(): void
    {
        $attribute = new RateLimitByVisitor('api');
        $key = $attribute->key;
        self::assertInstanceOf(\Closure::class, $key);

        self::assertSame('user:42', $key(['user' => $this->user(42)], $this->request()));
    }

    public function testKeyIsBuiltFromTheIpForAnonymousVisitor(): void
    {
        $attribute = new RateLimitByVisitor('api');
        $key = $attribute->key;
        self::assertInstanceOf(\Closure::class, $key);

        self::assertSame('ip:127.0.0.1', $key(['user' => null], $this->request()));
    }

    public function testMissingUserArgumentIsRejected(): void
    {
        $attribute = new RateLimitByVisitor('api');
        $key = $attribute->key;
        self::assertInstanceOf(\Closure::class, $key);

        $this->expectException(\LogicException::class);

        $key([], $this->request());
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
