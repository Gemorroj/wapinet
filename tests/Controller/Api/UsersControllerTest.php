<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class UsersControllerTest extends WebTestCaseWapinet
{
    /** @var int[] */
    private array $userIds = [];

    protected function tearDown(): void
    {
        if ([] !== $this->userIds) {
            static::getContainer()->get(Connection::class)->executeStatement(
                'DELETE FROM `user` WHERE id IN (?)',
                [$this->userIds],
                [ArrayParameterType::INTEGER],
            );
        }

        parent::tearDown();
    }

    public function testRegistrationCreatesUser(): void
    {
        $client = static::createClient();
        $username = 'reg_'.\bin2hex(\random_bytes(4));

        $client->jsonRequest('POST', '/api/auth/register', [
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => 'secret123',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        static::assertSame($username, $this->json($client)['username']);

        $this->userIds[] = $this->userId($username);
        static::assertNotEmpty($this->login($client, $username, 'secret123'));
    }

    public function testRegistrationRejectsDuplicateCredentials(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest('POST', '/api/auth/register', [
            'username' => $user['username'],
            'email' => 'other_'.\bin2hex(\random_bytes(4)).'@example.com',
            'password' => 'secret123',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $client->jsonRequest('POST', '/api/auth/register', [
            'username' => 'other_'.\bin2hex(\random_bytes(4)),
            'email' => $user['username'].'@example.com',
            'password' => 'secret123',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegistrationValidatesInput(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/auth/register', [
            'username' => 'x',
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUsersRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/users');

        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListShowsOnlineUsers(): void
    {
        $client = static::createClient();
        $viewer = $this->createUser();
        $other = $this->createUser(online: true);

        $client->request('GET', '/api/users', server: $this->auth($client, $viewer['username']));

        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        $usernames = \array_column($data['items'], 'username');

        static::assertContains($other['username'], $usernames);
        static::assertSame(1, $data['page']);
        static::assertGreaterThan(0, $data['perPage']);
    }

    public function testProfile(): void
    {
        $client = static::createClient();
        $viewer = $this->createUser();
        $other = $this->createUser(online: true);

        $client->request('GET', '/api/users/'.$other['username'], server: $this->auth($client, $viewer['username']));
        static::assertResponseIsSuccessful();

        $profile = $this->json($client);

        static::assertSame($other['username'], $profile['username']);
        static::assertFalse($profile['canEdit']);
        static::assertFalse($profile['isFriend']);
        static::assertSame(['files' => 0, 'gists' => 0, 'friends' => 0], $profile['counters']);

        $client->request('GET', '/api/users/'.$viewer['username'], server: $this->auth($client, $viewer['username']));
        static::assertResponseIsSuccessful();
        static::assertTrue($this->json($client)['canEdit']);
    }

    public function testProfileUpdate(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $auth = $this->auth($client, $user['username']);

        $client->jsonRequest('PUT', '/api/users/profile', [
            'sex' => 'f',
            'birthday' => '1990-05-05',
            'timezone' => 'Europe/Moscow',
            'country' => 'RU',
            'vk' => 'id123',
            'info' => 'Привет',
        ], server: $auth);

        static::assertResponseIsSuccessful();

        $profile = $this->json($client);

        static::assertSame('f', $profile['sex']);
        static::assertSame('RU', $profile['country']);
        static::assertSame('Россия', $profile['countryName']);
        static::assertSame('Europe/Moscow', $profile['timezone']);
        static::assertSame('1990-05-05', $profile['birthday']);
        static::assertSame('Привет', $profile['info']);
        static::assertSame('id123', $profile['vk']);
    }

    public function testProfileUpdateValidatesSex(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest('PUT', '/api/users/profile', ['sex' => 'x'], server: $this->auth($client, $user['username']));

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testSubscriberSettings(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $auth = $this->auth($client, $user['username']);

        $client->request('GET', '/api/users/subscriber', server: $auth);
        static::assertResponseIsSuccessful();
        static::assertSame(['emailNews' => false, 'emailFriends' => false], $this->json($client));

        $client->jsonRequest('PUT', '/api/users/subscriber', ['emailNews' => true, 'emailFriends' => true], server: $auth);
        static::assertResponseIsSuccessful();
        static::assertSame(['emailNews' => true, 'emailFriends' => true], $this->json($client));

        $client->request('GET', '/api/users/subscriber', server: $auth);
        static::assertSame(['emailNews' => true, 'emailFriends' => true], $this->json($client));
    }

    public function testFriends(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $friend = $this->createUser();
        $auth = $this->auth($client, $user['username']);

        $client->request('POST', '/api/users/'.$friend['username'].'/friends', server: $auth);
        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/users/'.$friend['username'], server: $auth);
        static::assertTrue($this->json($client)['isFriend']);

        $client->request('GET', '/api/users/'.$user['username'].'/friends', server: $auth);
        static::assertResponseIsSuccessful();

        $friends = $this->json($client);
        static::assertSame(1, $friends['total']);
        static::assertSame($friend['username'], $friends['items'][0]['username']);

        $client->request('POST', '/api/users/'.$friend['username'].'/friends', server: $auth);
        static::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $client->request('DELETE', '/api/users/'.$friend['username'].'/friends', server: $auth);
        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('DELETE', '/api/users/'.$friend['username'].'/friends', server: $auth);
        static::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testUnknownUserReturnsNotFound(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->request('GET', '/api/users/unknown_'.\bin2hex(\random_bytes(4)), server: $this->auth($client, $user['username']));

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        static::assertSame('Пользователь не найден.', $this->json($client)['detail'] ?? null);
    }

    public function testEventsAreEmptyForNewUser(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->request('GET', '/api/users/events', server: $this->auth($client, $user['username']));

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertSame([], $data['items']);
        static::assertSame(0, $data['total']);
    }

    /**
     * @return array{id: int, username: string}
     */
    private function createUser(bool $online = false): array
    {
        $connection = static::getContainer()->get(Connection::class);
        $username = 'api_user_'.\bin2hex(\random_bytes(4));

        $connection->executeStatement(
            'INSERT INTO `user` (username, email, password, enabled, created_at, last_activity, salt, roles) VALUES (?, ?, ?, 1, NOW(), ?, NULL, NULL)',
            [
                $username,
                $username.'@example.com',
                \password_hash('secret', \PASSWORD_DEFAULT),
                $online ? new \DateTimeImmutable()->format('Y-m-d H:i:s') : null,
            ],
        );

        $id = $this->userId($username);
        $this->userIds[] = $id;

        return ['id' => $id, 'username' => $username];
    }

    private function userId(string $username): int
    {
        return (int) static::getContainer()->get(Connection::class)->fetchOne(
            'SELECT id FROM `user` WHERE username = ?',
            [$username],
        );
    }

    /**
     * @return array{HTTP_AUTHORIZATION: string}
     */
    private function auth(KernelBrowser $client, string $username): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$this->login($client, $username)];
    }

    private function login(KernelBrowser $client, string $username, string $password = 'secret'): string
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        static::assertResponseIsSuccessful();

        return $this->json($client)['token'];
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
