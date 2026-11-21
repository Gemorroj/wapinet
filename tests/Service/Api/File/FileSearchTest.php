<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\File;

use App\Dto\Api\File\FileFilterQuery;
use App\Dto\Api\File\FileListQuery;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\Api\File\FilePresenter;
use App\Service\Api\File\FileSearch;
use App\Service\Search\SearchEngineInterface;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchResult;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[IgnoreDeprecations]
final class FileSearchTest extends KernelTestCase
{
    public function testSearchBuildsManticoreFilters(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $connection = $container->get(Connection::class);

        $tagName = 'search_tag_'.\bin2hex(\random_bytes(4));
        $connection->insert('tag', [
            'name' => $tagName,
            'count' => 0,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ]);
        $tagId = (int) $connection->lastInsertId();

        $username = 'search_user_'.\bin2hex(\random_bytes(4));
        $connection->insert('user', [
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => \password_hash('secret', \PASSWORD_DEFAULT),
            'enabled' => 1,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'salt' => null,
            'roles' => null,
        ]);
        $userId = (int) $connection->lastInsertId();

        $engine = new SearchEngineSpy();
        $search = $this->createSearch($engine);

        $response = $search->search(new FileListQuery(
            filter: new FileFilterQuery(
                q: 'hello',
                tag: $tagName,
                user: $username,
                date: 'today',
            ),
            sort: 'views',
        ));

        self::assertSame(0, $response->total);
        self::assertNotNull($engine->request);
        self::assertSame('files', $engine->request->index);
        self::assertSame('hello', $engine->request->text);
        self::assertSame([['count_views' => 'desc'], ['id' => 'desc']], $engine->request->sort);
        self::assertContains(['equals' => ['tag_id' => $tagId]], $engine->request->filters);
        self::assertContains(['equals' => ['user_id' => $userId]], $engine->request->filters);
        self::assertContains(
            ['range' => ['created_at_ts' => ['gte' => new \DateTimeImmutable('today')->getTimestamp()]]],
            $engine->request->filters,
        );
    }

    public function testUnknownTagReturnsEmptyResultWithoutSearch(): void
    {
        self::bootKernel();

        $engine = new SearchEngineSpy();
        $search = $this->createSearch($engine);

        $response = $search->search(new FileListQuery(filter: new FileFilterQuery(q: 'hello', tag: 'missing_tag_'.\bin2hex(\random_bytes(4)))));

        self::assertSame(0, $response->total);
        self::assertSame([], $response->items);
        self::assertNull($engine->request);
    }

    public function testSupportsOnlyPublicTextSearch(): void
    {
        self::bootKernel();

        $search = $this->createSearch(new SearchEngineSpy());

        self::assertTrue($search->supports(new FileListQuery(filter: new FileFilterQuery(q: 'hello')), null));
        self::assertFalse($search->supports(new FileListQuery(filter: new FileFilterQuery(q: 'hello', category: 'text')), null));
        self::assertFalse($search->supports(new FileListQuery(filter: new FileFilterQuery(q: 'hello', mine: true)), null));
        self::assertFalse($search->supports(new FileListQuery(), null));

        $connection = static::getContainer()->get(Connection::class);
        $adminUsername = 'search_admin_'.\bin2hex(\random_bytes(4));
        $connection->insert('user', [
            'username' => $adminUsername,
            'email' => $adminUsername.'@example.com',
            'password' => \password_hash('secret', \PASSWORD_DEFAULT),
            'enabled' => 1,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'salt' => null,
            'roles' => 'ROLE_ADMIN',
        ]);

        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['username' => $adminUsername]);

        self::assertNotNull($admin);
        self::assertFalse($search->supports(new FileListQuery(filter: new FileFilterQuery(q: 'hello')), $admin));
    }

    private function createSearch(SearchEngineInterface $engine): FileSearch
    {
        $container = static::getContainer();

        return new FileSearch(
            $engine,
            $container->get(FileRepository::class),
            $container->get(TagRepository::class),
            $container->get(UserRepository::class),
            $container->get(FilePresenter::class),
        );
    }
}

final class SearchEngineSpy implements SearchEngineInterface
{
    public ?SearchRequest $request = null;

    public function search(SearchRequest $request): SearchResult
    {
        $this->request = $request;

        return new SearchResult([], 0);
    }
}
