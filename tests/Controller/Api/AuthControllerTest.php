<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCaseWapinet
{
    /** @var int[] */
    private array $userIds = [];

    protected function tearDown(): void
    {
        if ([] !== $this->userIds) {
            $connection = static::getContainer()->get(Connection::class);
            $connection->executeStatement(
                'DELETE FROM api_token WHERE user_id IN (?)',
                [$this->userIds],
                [ArrayParameterType::INTEGER],
            );
            $connection->executeStatement(
                'DELETE FROM `user` WHERE id IN (?)',
                [$this->userIds],
                [ArrayParameterType::INTEGER],
            );
        }

        parent::tearDown();
    }

    public function testHealth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        static::assertResponseIsSuccessful();
        static::assertSame('ok', $this->json($client)['status']);
    }

    public function testLoginAndMe(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $user['username'],
            'password' => 'secret',
            'deviceName' => 'test',
        ]);
        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        static::assertIsString($data['token']);
        static::assertIsString($data['expiresAt']);
        static::assertSame($user['username'], $data['user']['username']);

        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.(string) $data['token']]);

        static::assertResponseIsSuccessful();
        static::assertSame($user['username'], $this->json($client)['username']);
    }

    public function testMeRequiresToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');

        static::assertResponseStatusCodeSame(401);
        static::assertStringStartsWith(
            'application/problem+json',
            (string) $client->getResponse()->headers->get('Content-Type')
        );
        static::assertSame('Требуется авторизация.', $this->json($client)['detail']);
    }

    public function testLoginRejectsInvalidPassword(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $user['username'],
            'password' => 'wrong',
        ]);

        static::assertResponseStatusCodeSame(401);
    }

    public function testLoginRejectsDisabledUser(): void
    {
        $client = static::createClient();
        $user = $this->createUser(enabled: false);

        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $user['username'],
            'password' => 'secret',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginRejectsNonJsonRequest(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [
            'username' => 'someone',
            'password' => 'secret',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        static::assertStringStartsWith(
            'application/problem+json',
            (string) $client->getResponse()->headers->get('Content-Type')
        );
    }

    public function testLogoutRevokesCurrentToken(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $client->request('POST', '/api/auth/logout', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testTokenListAndRevoke(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $other = $this->createUser();
        $ownerToken = $this->login($client, $owner['username'], 'laptop');
        $phoneToken = $this->login($client, $owner['username'], 'phone');
        $otherToken = $this->login($client, $other['username'], 'other');

        $client->request('GET', '/api/auth/tokens', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken]);
        static::assertResponseIsSuccessful();

        $items = $this->json($client)['items'];

        if (!\is_array($items)) {
            static::fail('Expected the token list to be an array.');
        }

        static::assertCount(2, $items);

        $currentIds = [];
        $phoneIds = [];

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            static::assertArrayNotHasKey('token', $item);
            static::assertArrayNotHasKey('tokenHash', $item);

            if (true === ($item['current'] ?? false)) {
                $currentIds[] = (int) $item['id'];
            }

            if ('phone' === ($item['name'] ?? null)) {
                $phoneIds[] = (int) $item['id'];
            }
        }

        static::assertCount(1, $currentIds);
        static::assertCount(1, $phoneIds);

        $client->request('DELETE', '/api/auth/tokens/'.$phoneIds[0], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken]);
        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$phoneToken]);
        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        static::assertSame('Недействительный токен.', $this->json($client)['detail']);

        $client->request('DELETE', '/api/auth/tokens/'.$currentIds[0], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('DELETE', '/api/auth/tokens/999999', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken]);
        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array{id: int, username: string}
     */
    private function createUser(bool $enabled = true): array
    {
        $connection = static::getContainer()->get(Connection::class);
        $username = 'api_test_'.\bin2hex(\random_bytes(4));

        $connection->executeStatement(
            'INSERT INTO `user` (username, email, password, enabled, created_at, salt, roles) VALUES (?, ?, ?, ?, NOW(), NULL, NULL)',
            [
                $username,
                $username.'@example.com',
                \password_hash('secret', \PASSWORD_DEFAULT),
                $enabled ? 1 : 0,
            ]
        );

        $id = (int) $connection->fetchOne('SELECT id FROM `user` WHERE username = ?', [$username]);
        $this->userIds[] = $id;

        return ['id' => $id, 'username' => $username];
    }

    private function login(KernelBrowser $client, string $username, string $deviceName = 'test'): string
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $username,
            'password' => 'secret',
            'deviceName' => $deviceName,
        ]);

        static::assertResponseIsSuccessful();

        return (string) $this->json($client)['token'];
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        return $data;
    }
}
