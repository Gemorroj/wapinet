<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class OnlineControllerTest extends WebTestCaseWapinet
{
    public function testPublicListHidesSensitiveFields(): void
    {
        $client = static::createClient();
        $browser = $this->createOnlineEntry();
        $client->request('GET', '/api/online');

        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        $item = $this->findItem($data, $browser);

        static::assertNull($item['ip']);
        static::assertNull($item['path']);
        static::assertGreaterThan(0, $data['count']);
    }

    public function testRequestIsTracked(): void
    {
        $client = static::createClient();
        $browser = 'tracked_browser_'.\bin2hex(\random_bytes(4));

        $client->request('GET', '/api/online', [], [], ['HTTP_USER_AGENT' => $browser]);

        static::assertResponseIsSuccessful();

        $item = $this->findItem($this->json($client), $browser);
        $connection = static::getContainer()->get(Connection::class);

        static::assertNull($item['ip']);
        static::assertSame('/api/online', $connection->fetchOne('SELECT path FROM online WHERE browser = ?', [$browser]));
    }

    public function testAdminSeesIpAndPath(): void
    {
        $client = static::createClient();
        $browser = $this->createOnlineEntry();
        $username = $this->createAdmin();
        $token = $this->login($client, $username);

        $client->request('GET', '/api/online', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        static::assertResponseIsSuccessful();

        $item = $this->findItem($this->json($client), $browser);

        static::assertSame('127.0.0.1', $item['ip']);
        static::assertSame('/test/path', $item['path']);
    }

    private function createOnlineEntry(): string
    {
        $connection = static::getContainer()->get(Connection::class);
        $browser = 'test_browser_'.\bin2hex(\random_bytes(4));

        $connection->insert('online', [
            'datetime' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'ip' => '127.0.0.1',
            'browser' => $browser,
            'path' => '/test/path',
        ]);

        return $browser;
    }

    private function createAdmin(): string
    {
        $connection = static::getContainer()->get(Connection::class);
        $username = 'api_online_admin_'.\bin2hex(\random_bytes(4));

        $connection->insert('user', [
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => \password_hash('secret', \PASSWORD_DEFAULT),
            'enabled' => 1,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'salt' => null,
            'roles' => 'ROLE_ADMIN',
        ]);

        return $username;
    }

    private function login(KernelBrowser $client, string $username): string
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $username,
            'password' => 'secret',
        ]);

        static::assertResponseIsSuccessful();

        return $this->json($client)['token'];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function findItem(array $data, string $browser): array
    {
        foreach ($data['items'] as $item) {
            if ($item['browser'] === $browser) {
                return $item;
            }
        }

        static::fail('Online entry not found.');
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
