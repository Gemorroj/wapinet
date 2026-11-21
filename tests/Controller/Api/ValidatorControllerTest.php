<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;

final class ValidatorControllerTest extends WebTestCaseWapinet
{
    public function testPhpValidatorInfoReturnsPhpVersion(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/php-validator');

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        self::assertSame(\PHP_VERSION, $data['phpVersion']);
        self::assertStringContainsString('max-age=3600', (string) $client->getResponse()->headers->get('Cache-Control'));
    }

    public function testPhpValidatorAcceptsValidCode(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-validator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => '<?php echo 1;']),
        );

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        self::assertTrue($data['valid']);
        self::assertSame([], $data['messages']);
    }

    public function testPhpValidatorReportsSyntaxError(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-validator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => '<?php if (']),
        );

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        self::assertFalse($data['valid']);
        self::assertSame('error', $data['messages'][0]['severity']);
        self::assertSame(1, $data['messages'][0]['line']);
        self::assertNotSame('', $data['messages'][0]['message']);
    }

    public function testPhpValidatorAddsOpeningTag(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-validator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => 'echo 1;']),
        );

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        self::assertTrue($data['valid']);
    }

    public function testValidatorsRejectEmptyCode(): void
    {
        $client = static::createClient();

        foreach (['php', 'html', 'css'] as $type) {
            $client->request(
                'POST',
                '/api/'.$type.'-validator',
                server: ['CONTENT_TYPE' => 'application/json'],
                content: (string) \json_encode(['code' => '']),
            );

            static::assertResponseStatusCodeSame(422);
        }
    }

    public function testValidatorRejectsMissingSource(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-validator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{}',
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testValidatorRejectsBothSources(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-validator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => '<?php echo 1;', 'url' => 'https://example.com/x.php']),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testValidatorRejectsBlockedUrl(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/php-validator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['url' => 'http://127.0.0.1/x.php']),
        );

        static::assertResponseStatusCodeSame(422);
    }

    public function testCssValidatorRejectsUnknownProfile(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/css-validator',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) \json_encode(['code' => 'body { color: red; }', 'profile' => 'css4']),
        );

        static::assertResponseStatusCodeSame(422);
    }
}
