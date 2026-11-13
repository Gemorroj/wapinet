<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class LoginControllerTest extends WebTestCaseWapinet
{
    private ?string $username = null;

    protected function tearDown(): void
    {
        if (null !== $this->username) {
            $connection = static::getContainer()->get(Connection::class);
            $connection->executeStatement('DELETE FROM `user` WHERE username = ?', [$this->username]);
        }

        parent::tearDown();
    }

    public function testAdminRedirectsToLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        // The test firewall uses HTTP basic as its entry point, while dev/prod
        // redirect anonymous visitors to the admin login form.
        if (Response::HTTP_UNAUTHORIZED === $client->getResponse()->getStatusCode()) {
            static::assertStringContainsString('Basic', (string) $client->getResponse()->headers->get('WWW-Authenticate'));

            return;
        }

        static::assertResponseStatusCodeSame(Response::HTTP_FOUND);
        static::assertStringEndsWith('/admin/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testLoginPageIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('_username', (string) $client->getResponse()->getContent());
        static::assertStringContainsString('_password', (string) $client->getResponse()->getContent());
    }

    public function testSuccessfulLoginRedirectsToAdmin(): void
    {
        $client = static::createClient();
        $this->createAdmin($client);

        $client->request('POST', '/admin/login_check', [
            '_username' => $this->username,
            '_password' => 'secret',
            '_csrf_token' => $this->csrfToken($client),
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_FOUND);
        static::assertStringEndsWith('/admin', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testWrongPasswordReturnsToLogin(): void
    {
        $client = static::createClient();
        $this->createAdmin($client);

        $client->request('POST', '/admin/login_check', [
            '_username' => $this->username,
            '_password' => 'wrong',
            '_csrf_token' => $this->csrfToken($client),
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_FOUND);
        static::assertStringEndsWith('/admin/login', (string) $client->getResponse()->headers->get('Location'));
    }

    private function createAdmin(KernelBrowser $client): void
    {
        $this->username = 'test_admin_login_'.\bin2hex(\random_bytes(4));
        $connection = $client->getContainer()->get(Connection::class);
        $connection->insert('user', [
            'username' => $this->username,
            'email' => $this->username.'@example.com',
            'password' => \password_hash('secret', \PASSWORD_DEFAULT),
            'enabled' => 1,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'salt' => null,
            'roles' => 'ROLE_SUPER_ADMIN',
        ]);
    }

    private function csrfToken(KernelBrowser $client): string
    {
        $client->request('GET', '/admin/login');
        $content = (string) $client->getResponse()->getContent();

        if (1 !== \preg_match('/name="_csrf_token" value="([^"]+)"/', $content, $matches)) {
            static::fail('CSRF token not found on the admin login page.');
        }

        return $matches[1];
    }
}
