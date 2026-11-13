<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WebTestCaseWapinet extends WebTestCase
{
    protected function getFixturesPath(): string
    {
        return __DIR__.'/fixtures';
    }

    protected static function loginAdmin(): KernelBrowser
    {
        $client = static::createClient();

        $connection = static::getContainer()->get(Connection::class);
        $username = 'test_admin_'.\bin2hex(\random_bytes(4));

        $connection->insert('user', [
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => \password_hash('secret', \PASSWORD_DEFAULT),
            'enabled' => 1,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'salt' => null,
            'roles' => 'ROLE_SUPER_ADMIN',
        ]);

        $client->setServerParameter('PHP_AUTH_USER', $username);
        $client->setServerParameter('PHP_AUTH_PW', 'secret');
        $client->setServerParameter('TEST_USERNAME', $username);

        return $client;
    }
}
