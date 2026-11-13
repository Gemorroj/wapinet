<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\Connection;

final class TagsControllerTest extends WebTestCaseWapinet
{
    public function testListFiltersByName(): void
    {
        $client = static::createClient();
        $connection = static::getContainer()->get(Connection::class);

        $name = 'api_tag_'.\bin2hex(\random_bytes(4));
        $connection->insert('tag', [
            'name' => $name,
            'count' => 5,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ]);

        $client->request('GET', '/api/tags?filter[q]='.$name);

        static::assertResponseIsSuccessful();

        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(1, $data['total']);
        static::assertSame($name, $data['items'][0]['name']);
        static::assertSame(5, $data['items'][0]['count']);
    }
}
