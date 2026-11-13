<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;

final class FileStatisticsTest extends WebTestCaseWapinet
{
    public function testStatisticsArePublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/files/statistics');

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('countFiles', $data);
        static::assertArrayHasKey('sizeFiles', $data);
        static::assertArrayHasKey('countViews', $data);
        static::assertArrayHasKey('users', $data);
        static::assertSame(
            ['video', 'audio', 'image', 'text', 'office', 'archive', 'android', 'java', 'windows', 'playlist', 'torrent', 'other'],
            \array_keys($data['categories']),
        );
    }
}
