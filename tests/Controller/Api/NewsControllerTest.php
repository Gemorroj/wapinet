<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

final class NewsControllerTest extends WebTestCaseWapinet
{
    /** @var list<int> */
    private array $newsIds = [];

    protected function tearDown(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        foreach ($this->newsIds as $id) {
            $connection->delete('news', ['id' => $id]);
        }

        parent::tearDown();
    }

    public function testListReturnsNews(): void
    {
        $client = static::createClient();
        $subject = 'news_'.\bin2hex(\random_bytes(4));

        $this->newsIds[] = $this->createNews($subject, 'Body text');

        $client->request('GET', '/api/news?page=1');

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertGreaterThanOrEqual(1, $data['total']);
        static::assertSame($subject, $data['items'][0]['subject']);
    }

    public function testShowRendersNewsBody(): void
    {
        $client = static::createClient();
        $id = $this->createNews('news_'.\bin2hex(\random_bytes(4)), '[b]Bold[/b] body');

        $this->newsIds[] = $id;

        $client->request('GET', '/api/news/'.$id);

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        static::assertSame($id, $data['id']);
        static::assertStringContainsString('<strong', $data['bodyHtml']);
    }

    public function testShowReturnsNotFoundForUnknownNews(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/news/999999999');

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createNews(string $subject, string $body): int
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->insert('news', [
            'subject' => $subject,
            'body' => $body,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @return array<string, mixed>
     */
    private function json(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): array
    {
        return \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
