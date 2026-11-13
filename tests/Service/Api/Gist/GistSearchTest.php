<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Gist;

use App\Dto\Api\Gist\GistFilterQuery;
use App\Dto\Api\Gist\GistListQuery;
use App\Repository\GistRepository;
use App\Repository\UserRepository;
use App\Service\Api\Gist\GistPresenter;
use App\Service\Api\Gist\GistSearch;
use App\Service\Search\SearchEngineInterface;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchResult;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[IgnoreDeprecations]
final class GistSearchTest extends KernelTestCase
{
    public function testSearchBuildsManticoreFilters(): void
    {
        self::bootKernel();

        $connection = static::getContainer()->get(Connection::class);
        $username = 'gist_search_'.\bin2hex(\random_bytes(4));

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

        $engine = new GistSearchEngineSpy();
        $search = $this->createSearch($engine);

        $response = $search->search(new GistListQuery(
            filter: new GistFilterQuery(q: 'hello', user: $username),
            sort: 'oldest',
        ));

        self::assertSame(0, $response->total);
        self::assertNotNull($engine->request);
        self::assertSame('gist', $engine->request->index);
        self::assertSame('hello', $engine->request->text);
        self::assertSame([['created_at_ts' => 'asc'], ['id' => 'asc']], $engine->request->sort);
        self::assertContains(['equals' => ['user_id' => $userId]], $engine->request->filters);

        $connection->delete('user', ['id' => $userId]);
    }

    public function testUnknownUserReturnsEmptyResultWithoutSearch(): void
    {
        self::bootKernel();

        $engine = new GistSearchEngineSpy();
        $search = $this->createSearch($engine);

        $response = $search->search(new GistListQuery(filter: new GistFilterQuery(q: 'hello', user: 'missing_'.\bin2hex(\random_bytes(4)))));

        self::assertSame(0, $response->total);
        self::assertSame([], $response->items);
        self::assertNull($engine->request);
    }

    public function testSupportsOnlyTextSearch(): void
    {
        self::bootKernel();

        $search = $this->createSearch(new GistSearchEngineSpy());

        self::assertTrue($search->supports(new GistListQuery(filter: new GistFilterQuery(q: 'hello'))));
        self::assertFalse($search->supports(new GistListQuery(filter: new GistFilterQuery(q: '   '))));
        self::assertFalse($search->supports(new GistListQuery()));
    }

    private function createSearch(SearchEngineInterface $engine): GistSearch
    {
        $container = static::getContainer();

        return new GistSearch(
            $engine,
            $container->get(GistRepository::class),
            $container->get(UserRepository::class),
            $container->get(GistPresenter::class),
        );
    }
}

final class GistSearchEngineSpy implements SearchEngineInterface
{
    public ?SearchRequest $request = null;

    public function search(SearchRequest $request): SearchResult
    {
        $this->request = $request;

        return new SearchResult([], 0);
    }
}
