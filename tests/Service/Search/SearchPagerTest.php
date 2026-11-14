<?php

declare(strict_types=1);

namespace App\Tests\Service\Search;

use App\Entity\User;
use App\Service\Search\SearchPager;
use App\Service\Search\SearchResult;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class SearchPagerTest extends TestCase
{
    public function testEmptyResultDoesNotQueryTheDatabase(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('createQueryBuilder');

        $pager = new SearchPager($entityManager)->paginate(User::class, new SearchResult([], 0), 1, 20);

        self::assertSame(0, $pager->getNbResults());
        self::assertSame([], $pager->getCurrentPageResults());
    }

    public function testKeepsTheOrderOfTheSearchHits(): void
    {
        $rows = [$this->row(1), $this->row(2), $this->row(3)];

        $pager = new SearchPager($this->entityManager($rows))->paginate(User::class, new SearchResult([3, 1], 2), 1, 20);

        self::assertSame([3, 1], \array_map(static fn (User $user): int => $user->getId(), $pager->getCurrentPageResults()));
        self::assertSame(2, $pager->getNbResults());
    }

    public function testSkipsDeletedEntities(): void
    {
        $pager = new SearchPager($this->entityManager([$this->row(1)]))->paginate(User::class, new SearchResult([1, 2], 2), 1, 20);

        self::assertSame([1], \array_map(static fn (User $user): int => $user->getId(), $pager->getCurrentPageResults()));
    }

    public function testClampsThePageToTheAvailableRange(): void
    {
        $entityManager = $this->entityManager([$this->row(1), $this->row(2), $this->row(3)]);

        $first = new SearchPager($entityManager)->paginate(User::class, new SearchResult([1, 2, 3], 3), 0, 2);
        $last = new SearchPager($entityManager)->paginate(User::class, new SearchResult([1, 2, 3], 3), 99, 2);

        self::assertSame(1, $first->getCurrentPage());
        self::assertSame(2, $last->getCurrentPage());
        self::assertSame(2, $last->getMaxPerPage());
    }

    /**
     * @param list<User> $rows
     */
    private function entityManager(array $rows): EntityManagerInterface
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('getIdentifierValues')->willReturnCallback(
            static fn (User $row): array => ['id' => $row->getId()],
        );

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($rows);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturn($metadata);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        return $entityManager;
    }

    private function row(int $id): User
    {
        $user = new User();
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setValue($user, $id);

        return $user;
    }
}
