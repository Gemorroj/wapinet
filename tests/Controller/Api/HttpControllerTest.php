<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Symfony\Component\HttpFoundation\Response;

final class HttpControllerTest extends WebTestCaseWapinet
{
    public function testBlocksPrivateAddresses(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/http', ['url' => 'http://127.0.0.1/', 'method' => 'GET']);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        static::assertIsString($data['detail'] ?? null);
        static::assertStringContainsString('приватным', $data['detail']);
    }

    public function testRejectsEmptyUrl(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/http', ['method' => 'GET']);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}

