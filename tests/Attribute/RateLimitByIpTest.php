<?php

declare(strict_types=1);

namespace App\Tests\Attribute;

use App\Attribute\RateLimitByIp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RateLimitByIpTest extends TestCase
{
    public function testKeyIsBuiltFromTheClientIp(): void
    {
        $attribute = new RateLimitByIp('api');
        self::assertTrue($attribute->exposeHeaders);
        $key = $attribute->key;
        self::assertInstanceOf(\Closure::class, $key);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        self::assertSame('ip:127.0.0.1', $key([], $request));
    }
}
