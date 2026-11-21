<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;

final class BrowserInfoControllerTest extends WebTestCaseWapinet
{
    public function testReturnsClientRequestInformationWithoutSecrets(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/browser-info', [
            'webrtc' => '8.8.8.8,invalid,127.0.0.1',
        ], server: [
            'HTTP_USER_AGENT' => 'Wapinet test browser',
            'HTTP_ACCEPT_LANGUAGE' => 'ru-RU,ru;q=0.9',
            'HTTP_X_FORWARDED_FOR' => '127.0.0.1',
            'HTTP_X_API_KEY' => 'secret-key',
            'HTTP_COOKIE' => 'session=secret',
        ]);

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        static::assertSame('Wapinet test browser', $data['userAgent']);
        static::assertSame('ru-RU,ru;q=0.9', $data['language']);
        static::assertSame('127.0.0.1', $data['proxy']);
        static::assertSame('GET', $data['method']);
        static::assertArrayHasKey('geoDatabase', $data);
        static::assertArrayHasKey('insight', $data);
        static::assertNull($data['insight']);
        static::assertArrayHasKey('proxyGeo', $data);
        static::assertArrayHasKey('webrtcGeo', $data);
        static::assertSame('127.0.0.1', $data['proxyGeo'][0]['ip']);
        static::assertSame('8.8.8.8', $data['webrtcGeo'][0]['ip']);
        static::assertCount(2, $data['webrtcGeo']);
        static::assertArrayHasKey('vpn', $data);
        static::assertFalse($data['vpn']['suspicious']);
        static::assertArrayHasKey('headers', $data);
        static::assertSame(['[скрыто]'], $data['headers']['x-api-key']);
        static::assertSame(['[скрыто]'], $data['headers']['cookie']);
        static::assertStringNotContainsString('secret-key', (string) \json_encode($data));
        static::assertStringNotContainsString('session=secret', (string) \json_encode($data));
    }
}
