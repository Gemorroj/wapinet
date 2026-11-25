<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class GistsControllerTest extends WebTestCaseWapinet
{
    /** @var list<int> */
    private array $gistIds = [];

    /** @var list<int> */
    private array $userIds = [];

    protected function tearDown(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        foreach ($this->gistIds as $id) {
            $connection->delete('gist', ['id' => $id]);
        }

        foreach ($this->userIds as $id) {
            $connection->delete('user', ['id' => $id]);
        }

        parent::tearDown();
    }

    public function testListReturnsBlogPosts(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $subject = 'gist_'.\bin2hex(\random_bytes(4));

        $this->gistIds[] = $this->createGist($user['id'], $subject, 'Body text');

        $client->request('GET', '/api/gists?page=1');

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertGreaterThanOrEqual(1, $data['total']);
        static::assertSame($subject, $data['items'][0]['subject']);
        static::assertSame($user['username'], $data['items'][0]['user']['username']);
        static::assertFalse($data['items'][0]['canEdit']);
        static::assertFalse($data['items'][0]['canDelete']);
    }

    public function testListSortsByOldest(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $this->gistIds[] = $this->createGist($user['id'], 'first post', 'Body');
        $this->gistIds[] = $this->createGist($user['id'], 'second post', 'Body');

        $client->request('GET', '/api/gists?sort=oldest&filter%5Buser%5D='.\rawurlencode($user['username']));

        static::assertResponseIsSuccessful();

        $items = $this->json($client)['items'];

        static::assertSame('first post', $items[0]['subject']);
        static::assertSame('second post', $items[1]['subject']);
    }

    public function testShowRendersBody(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $id = $this->createGist($user['id'], 'gist_'.\bin2hex(\random_bytes(4)), '[b]Bold[/b] body');

        $this->gistIds[] = $id;

        $client->request('GET', '/api/gists/'.$id);

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertSame($id, $data['id']);
        static::assertStringContainsString('<strong', $data['bodyHtml']);
    }

    public function testShowReturnsNotFoundForUnknownGist(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/gists/999999999');

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAuthenticatedUserCanCreateBlogPost(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest(
            'POST',
            '/api/gists',
            ['subject' => 'New post', 'body' => 'Text'],
            $this->auth($client, $user['username']),
        );

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = $this->json($client);

        static::assertSame('New post', $data['subject']);
        static::assertSame($user['username'], $data['user']['username']);
        static::assertTrue($data['canEdit']);
        static::assertTrue($data['canDelete']);

        $this->gistIds[] = $data['id'];
    }

    public function testEmptySubjectIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest(
            'POST',
            '/api/gists',
            ['subject' => '', 'body' => 'Text'],
            $this->auth($client, $user['username']),
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTooLongBodyIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser();

        $client->jsonRequest(
            'POST',
            '/api/gists',
            ['subject' => 'Subject', 'body' => \str_repeat('a', 5001)],
            $this->auth($client, $user['username']),
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testOwnerCanUpdateAndDeleteBlogPost(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $id = $this->createGist($owner['id'], 'Old subject', 'Old body');

        $this->gistIds[] = $id;

        $auth = $this->auth($client, $owner['username']);

        $client->jsonRequest('PATCH', '/api/gists/'.$id, ['subject' => 'New subject', 'body' => 'New body'], $auth);

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertSame('New subject', $data['subject']);
        static::assertNotNull($data['updatedAt']);

        $client->jsonRequest('DELETE', '/api/gists/'.$id, [], $auth);

        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testUpdateAcceptsStructuredJsonSuffixMediaType(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $id = $this->createGist($owner['id'], 'Old subject', 'Old body');

        $this->gistIds[] = $id;

        $auth = $this->auth($client, $owner['username']);

        $client->request(
            'PATCH',
            '/api/gists/'.$id,
            server: $auth + ['CONTENT_TYPE' => 'application/merge-patch+json'],
            content: \json_encode(['subject' => 'New subject', 'body' => 'New body'], \JSON_THROW_ON_ERROR),
        );

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertSame('New subject', $data['subject']);
        static::assertSame('New body', $data['body']);
    }

    public function testOtherUserCannotUpdateOrDeleteBlogPost(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $other = $this->createUser();
        $id = $this->createGist($owner['id'], 'Owner subject', 'Owner body');

        $this->gistIds[] = $id;

        $auth = $this->auth($client, $other['username']);

        $client->jsonRequest('PATCH', '/api/gists/'.$id, ['subject' => 'Hacked', 'body' => 'Hacked'], $auth);

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertSame('Доступ запрещён.', $this->json($client)['detail']);

        $client->jsonRequest('DELETE', '/api/gists/'.$id, [], $auth);

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createGist(int $userId, string $subject, string $body): int
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->insert('gist', [
            'subject' => $subject,
            'body' => $body,
            'user_id' => $userId,
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
        $username = 'gist_'.\bin2hex(\random_bytes(4));

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
