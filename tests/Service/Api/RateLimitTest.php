<?php

declare(strict_types=1);

namespace App\Tests\Service\Api;

use App\Entity\User;
use App\Service\Api\RateLimit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit as LimiterResult;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final class RateLimitTest extends TestCase
{
    public function testConsumesIpKeyForAnonymousRequest(): void
    {
        RateLimit::consumeForIp($this->factory('ip:127.0.0.1'), $this->request());
    }

    public function testUsesIpForAnonymousVisitor(): void
    {
        RateLimit::consumeForVisitor($this->factory('ip:127.0.0.1'), $this->request(), null);
    }

    public function testUsesUserIdForAuthorizedVisitor(): void
    {
        RateLimit::consumeForVisitor($this->factory('user:42'), $this->request(), $this->user(42));
    }

    public function testConsumesUserKey(): void
    {
        RateLimit::consumeForUser($this->factory('user:42'), $this->user(42));
    }

    public function testThrows429WithRetryAfter(): void
    {
        $limiter = $this->factory('ip:127.0.0.1', $this->limiterResult(false, 90));

        try {
            RateLimit::consumeForIp($limiter, $this->request());
            self::fail('Expected TooManyRequestsHttpException.');
        } catch (TooManyRequestsHttpException $exception) {
            self::assertSame(429, $exception->getStatusCode());
            self::assertSame(90, $exception->getHeaders()['Retry-After']);
        }
    }

    private function factory(string $key, ?LimiterResult $result = null): RateLimiterFactoryInterface
    {
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects(self::once())->method('consume')->willReturn($result ?? $this->limiterResult(true));

        $factory = $this->createMock(RateLimiterFactoryInterface::class);
        $factory->expects(self::once())->method('create')->with($key)->willReturn($limiter);

        return $factory;
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

    private function limiterResult(bool $accepted, int $retryAfter = 60): LimiterResult
    {
        return new LimiterResult(0, new \DateTimeImmutable('+'.$retryAfter.' seconds'), $accepted, 100);
    }
}
