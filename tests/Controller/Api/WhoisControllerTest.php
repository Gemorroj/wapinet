<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;

final class WhoisControllerTest extends WebTestCaseWapinet
{
    public function testLookupReturnsStructuredResponse(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/whois',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['query' => 'example.com', 'protocol' => 'auto']),
        );

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        static::assertSame('example.com', $data['query']);
        static::assertSame('domain', $data['kind']);
        static::assertSame('rdap', $data['protocol']);
        static::assertSame('https://rdap.example.test/', $data['server']);
        static::assertContains('active', $data['statuses']);
        static::assertSame(['ns1.example.com', 'ns2.example.com'], $data['nameservers']);
        static::assertSame('Example Registrar', $data['contacts'][0]['name']);
        static::assertArrayHasKey('dns', $data);
    }

    public function testEmptyQueryUsesClientIp(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/whois',
            server: ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '192.0.2.10'],
            content: (string) \json_encode(['query' => '']),
        );

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        static::assertSame('192.0.2.10', $data['query']);
        static::assertSame('ipv4', $data['kind']);
    }

    public function testRejectsInvalidProtocol(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/whois',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['query' => 'example.com', 'protocol' => 'ftp']),
        );

        static::assertResponseStatusCodeSame(422);
    }
}
