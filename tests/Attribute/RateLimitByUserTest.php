<?php

declare(strict_types=1);

namespace App\Tests\Attribute;

use App\Attribute\RateLimitByUser;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class RateLimitByUserTest extends TestCase
{
    public function testKeyIsBuiltFromTheCurrentUser(): void
    {
        $attribute = new RateLimitByUser('api');
        $key = $attribute->key;
        self::assertInstanceOf(\Closure::class, $key);

        self::assertSame('user:42', $key(['user' => $this->user(42)]));
    }

    public function testMissingUserArgumentIsRejected(): void
    {
        $attribute = new RateLimitByUser('api');
        $key = $attribute->key;
        self::assertInstanceOf(\Closure::class, $key);

        $this->expectException(\LogicException::class);

        $key([]);
    }

    private function user(int $id): User
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
