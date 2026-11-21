<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\User;

use App\Service\Api\User\UserSearch;
use App\Service\Search\SearchEngineInterface;
use App\Service\Search\SearchPager;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchResult;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UserSearchTest extends TestCase
{
    public function testSearchesTheUsersIndexWithStableOrdering(): void
    {
        $engine = new class implements SearchEngineInterface {
            public ?SearchRequest $request = null;

            public function search(SearchRequest $request): SearchResult
            {
                $this->request = $request;

                return new SearchResult([], 7);
            }
        };

        $pager = new UserSearch($engine, new SearchPager($this->createMock(EntityManagerInterface::class)), 15)
            ->search('bob', 3);

        self::assertNotNull($engine->request);
        self::assertSame('users', $engine->request->index);
        self::assertSame('bob', $engine->request->text);
        self::assertSame([['_score' => 'desc'], ['id' => 'desc']], $engine->request->sort);
        self::assertSame([], $engine->request->filters);
        self::assertSame(3, $engine->request->page);
        self::assertSame(15, $engine->request->perPage);

        self::assertSame(7, $pager->getNbResults());
        self::assertSame(15, $pager->getMaxPerPage());
        self::assertSame(1, $pager->getCurrentPage());
    }
}
