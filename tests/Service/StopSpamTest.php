<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\StopSpam;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

#[AllowMockObjectsWithoutExpectations]
final class StopSpamTest extends TestCase
{
    public function testBlocksReportedIp(): void
    {
        $stopSpam = new StopSpam(
            $this->security(),
            new MockHttpClient(new MockResponse((string) \json_encode([
                'success' => 1,
                'ip' => [['value' => '127.0.0.1', 'appears' => 1, 'frequency' => 100]],
            ]))),
        );

        $this->expectException(AccessDeniedException::class);

        $stopSpam->checkRequest($this->request());
    }

    public function testAllowsCleanIp(): void
    {
        $this->expectNotToPerformAssertions();

        $stopSpam = new StopSpam(
            $this->security(),
            new MockHttpClient(new MockResponse((string) \json_encode([
                'success' => 1,
                'ip' => [['value' => '127.0.0.1', 'appears' => 0]],
            ]))),
        );

        $stopSpam->checkRequest($this->request());
    }

    public function testFailsOpenWhenTheServiceIsUnavailable(): void
    {
        $this->expectNotToPerformAssertions();

        $stopSpam = new StopSpam(
            $this->security(),
            new MockHttpClient(static fn (): MockResponse => new MockResponse('error', ['http_code' => 500])),
        );

        $stopSpam->checkRequest($this->request());
    }

    public function testSkipsTheCheckForAuthenticatedUsers(): void
    {
        $requests = 0;
        $user = $this->createMock(User::class);
        $stopSpam = new StopSpam(
            $this->security($user),
            new MockHttpClient(static function () use (&$requests): MockResponse {
                ++$requests;

                return new MockResponse('{}');
            }),
        );

        $stopSpam->checkRequest($this->request());

        self::assertSame(0, $requests);
    }

    private function request(): Request
    {
        return Request::create('/', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1']);
    }

    private function security(?UserInterface $user = null): Security
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(
            null === $user ? null : new UsernamePasswordToken($user, 'main', $user->getRoles()),
        );

        $container = new class($tokenStorage) implements ContainerInterface {
            public function __construct(private readonly TokenStorageInterface $storage)
            {
            }

            public function get(string $id): mixed
            {
                return $this->storage;
            }

            public function has(string $id): bool
            {
                return true;
            }
        };

        return new Security($container);
    }
}
