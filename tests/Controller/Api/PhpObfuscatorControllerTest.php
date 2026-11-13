<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;

final class PhpObfuscatorControllerTest extends WebTestCaseWapinet
{
    public function testInfoReturnsPhpVersionAndLimit(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/php-obfuscator');

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        self::assertSame(\PHP_VERSION, $data['phpVersion']);
        self::assertSame(262144, $data['maxCodeSize']);
        self::assertStringContainsString('max-age=3600', (string) $client->getResponse()->headers->get('Cache-Control'));
    }

    public function testObfuscatesCode(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-obfuscator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => '<?php echo "Hello World";']),
        );

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        self::assertStringStartsWith('<?php', $data['code']);
        self::assertStringNotContainsString('Hello World', $data['code']);
    }

    public function testRejectsInvalidPhp(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-obfuscator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => '<?php if (']),
        );

        static::assertResponseStatusCodeSame(422);

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));
        self::assertStringContainsString('строка 1', $data['detail']);
    }

    public function testRejectsEmptyCode(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-obfuscator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => '']),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsMissingSource(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-obfuscator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{}',
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsBothSources(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-obfuscator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => '<?php echo 1;', 'url' => 'https://example.com/a.php']),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsBlockedUrl(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-obfuscator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['url' => 'http://127.0.0.1/a.php']),
        );

        static::assertResponseStatusCodeSame(422);
    }
}

