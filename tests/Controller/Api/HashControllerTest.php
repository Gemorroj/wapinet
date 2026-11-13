<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;

final class HashControllerTest extends WebTestCaseWapinet
{
    private const array ALGORITHMS = ['md5', 'crc32'];

    public function testRejectsInvalidUrl(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/hash',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['url' => 'not-a-url', 'algorithms' => self::ALGORITHMS]),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsMissingUrl(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/hash',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['algorithms' => self::ALGORITHMS]),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsMissingAlgorithms(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/hash',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['url' => 'https://example.com/abc.txt']),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsPrivateAddresses(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/hash',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['url' => 'http://127.0.0.1/secret.txt', 'algorithms' => self::ALGORITHMS]),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsUnknownAlgorithm(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/hash',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['url' => 'https://example.com/abc.txt', 'algorithms' => ['md5', 'unknown']]),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsEmptyAlgorithmList(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/hash',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['url' => 'https://example.com/abc.txt', 'algorithms' => []]),
        );

        static::assertResponseStatusCodeSame(422);
    }
}

