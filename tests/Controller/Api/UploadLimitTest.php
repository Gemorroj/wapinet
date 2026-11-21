<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class UploadLimitTest extends WebTestCaseWapinet
{
    public function testRejectsOversizedFileUpload(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/files', [], [], [
            'CONTENT_LENGTH' => '104857600',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        static::assertSame(
            'Файл слишком большой: за один запрос можно загрузить не более 50 МБ.',
            $this->json($client)['detail'],
        );
    }

    public function testRejectsOversizedArchiverRequest(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/archiver/create', [], [], [
            'CONTENT_LENGTH' => '104857600',
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
