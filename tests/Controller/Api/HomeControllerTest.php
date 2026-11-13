<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class HomeControllerTest extends WebTestCaseWapinet
{
    public function testHomeCountersArePublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/home');

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertSame(
            ['files', 'gists', 'guestbook', 'online', 'newsLastDate', 'forumTopics', 'forumPosts'],
            \array_keys($data),
        );
        static::assertGreaterThanOrEqual(0, $data['files']);
        static::assertGreaterThanOrEqual(0, $data['gists']);
        static::assertGreaterThanOrEqual(0, $data['guestbook']);
        static::assertGreaterThanOrEqual(0, $data['online']);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
