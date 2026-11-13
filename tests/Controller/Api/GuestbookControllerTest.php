<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class GuestbookControllerTest extends WebTestCaseWapinet
{
    /** @var list<int> */
    private array $messageIds = [];

    /** @var list<int> */
    private array $userIds = [];

    protected function tearDown(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        foreach ($this->messageIds as $id) {
            $connection->delete('guestbook', ['id' => $id]);
        }

        foreach ($this->userIds as $id) {
            $connection->delete('user', ['id' => $id]);
        }

        parent::tearDown();
    }

    public function testListReturnsMessages(): void
    {
        $client = static::createClient();
        $message = 'guestbook_'.\bin2hex(\random_bytes(4));

        $this->messageIds[] = $this->createMessage($message);

        $client->request('GET', '/api/guestbook?page=1');

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertGreaterThanOrEqual(1, $data['total']);
        static::assertSame($message, $data['items'][0]['message']);
        static::assertNull($data['items'][0]['user']);
    }

    public function testAuthenticatedUserCanCreateMessage(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest(
            'POST',
            '/api/guestbook',
            ['message' => 'Authenticated message'],
            $this->auth($client, $user['username']),
        );

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = $this->json($client);

        static::assertSame('Authenticated message', $data['message']);
        static::assertSame($user['username'], $data['user']['username']);

        $this->messageIds[] = $data['id'];
    }

    public function testEmptyMessageIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/guestbook', ['message' => '']);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testInsightForLocalAddressIsNotFound(): void
    {
        $client = static::createClient();
        $id = $this->createMessage('guestbook_insight_'.\bin2hex(\random_bytes(4)));
        $this->messageIds[] = $id;

        $client->request('GET', '/api/guestbook/'.$id.'/insight');

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createMessage(string $message): int
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->insert('guestbook', [
            'message' => $message,
            'ip' => '127.0.0.1',
            'browser' => 'test',
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @return array{id: int, username: string}
     */
    private function createUser(): array
    {
        $connection = static::getContainer()->get(Connection::class);
        $username = 'guestbook_'.\bin2hex(\random_bytes(4));

        $connection->insert('user', [
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => \password_hash('secret', \PASSWORD_DEFAULT),
            'enabled' => 1,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'salt' => null,
            'roles' => null,
        ]);

        $id = (int) $connection->lastInsertId();
        $this->userIds[] = $id;

        return ['id' => $id, 'username' => $username];
    }

    /**
     * @return array<string, string>
     */
    private function auth(KernelBrowser $client, string $username): array
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $username,
            'password' => 'secret',
        ]);

        $token = $this->json($client)['token'];

        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
