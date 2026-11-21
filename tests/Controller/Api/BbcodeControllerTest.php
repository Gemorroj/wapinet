<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Symfony\Component\HttpFoundation\Response;

final class BbcodeControllerTest extends WebTestCaseWapinet
{
    public function testPreviewRendersBbcode(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/bbcode/preview', ['text' => '[b]жирный[/b]']);

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertStringContainsString('<strong', $data['html']);
        static::assertStringContainsString('жирный', $data['html']);
    }

    public function testPreviewRendersCodeWithoutGeshi(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/bbcode/preview', ['text' => '[php]echo 1;[/php]']);

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertStringContainsString('<pre><code class="language-php">', $data['html']);
        static::assertStringNotContainsString('<span style=', $data['html']);
    }

    public function testEmptyPreviewIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/bbcode/preview', ['text' => '']);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
