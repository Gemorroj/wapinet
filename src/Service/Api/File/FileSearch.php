<?php

declare(strict_types=1);

namespace App\Service\Api\File;

use App\Dto\Api\File\FileListQuery;
use App\Dto\Api\File\FileListResponse;
use App\Entity\User;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\Search\SearchEngineInterface;
use App\Service\Search\SearchRequest;

final readonly class FileSearch
{
    private const string INDEX = 'files';

    public function __construct(
        private SearchEngineInterface $engine,
        private FileRepository $fileRepository,
        private TagRepository $tagRepository,
        private UserRepository $userRepository,
        private FilePresenter $presenter,
    ) {
    }

    public function supports(FileListQuery $query, ?User $viewer): bool
    {
        if (null === $query->filter->q || '' === \trim($query->filter->q)) {
            return false;
        }

        if ($query->filter->mine || null !== $query->filter->category || null !== $query->filter->hidden) {
            return false;
        }

        if (null !== $viewer && $viewer->isAdmin()) {
            return false;
        }

        return true;
    }

    public function search(FileListQuery $query): FileListResponse
    {
        $filters = [];

        if (null !== $query->filter->tag && '' !== $query->filter->tag) {
            $tag = $this->tagRepository->getTagByName($query->filter->tag);

            if (null === $tag) {
                return $this->empty($query);
            }

            $filters[] = ['equals' => ['tag_id' => (int) $tag->getId()]];
        }

        if (null !== $query->filter->user && '' !== $query->filter->user) {
            $user = $this->userRepository->loadUserByIdentifier($query->filter->user);

            if (null === $user) {
                return $this->empty($query);
            }

            $filters[] = ['equals' => ['user_id' => (int) $user->getId()]];
        }

        if ('today' === $query->filter->date) {
            $filters[] = ['range' => ['created_at_ts' => ['gte' => new \DateTimeImmutable('today')->getTimestamp()]]];
        } elseif ('yesterday' === $query->filter->date) {
            $filters[] = ['range' => ['created_at_ts' => [
                'gte' => new \DateTimeImmutable('yesterday')->getTimestamp(),
                'lt' => new \DateTimeImmutable('today')->getTimestamp(),
            ]]];
        }

        $result = $this->engine->search(new SearchRequest(
            self::INDEX,
            \trim((string) $query->filter->q),
            $filters,
            $this->sort($query->sort),
            $query->page,
            $query->perPage,
        ));

        $files = $this->fileRepository->findPublicByIds($result->ids);

        return new FileListResponse(
            \array_map($this->presenter->toListItem(...), $files),
            $result->total,
            $query->page,
            $query->perPage,
        );
    }

    /**
     * @return list<array<string, string>>
     */
    private function sort(string $sort): array
    {
        return match ($sort) {
            'oldest' => [['created_at_ts' => 'asc'], ['id' => 'asc']],
            'name' => [['original_file_name' => 'asc'], ['id' => 'desc']],
            'views' => [['count_views' => 'desc'], ['id' => 'desc']],
            'relevance' => [['_score' => 'desc'], ['id' => 'desc']],
            default => [['created_at_ts' => 'desc'], ['id' => 'desc']],
        };
    }

    private function empty(FileListQuery $query): FileListResponse
    {
        return new FileListResponse([], 0, $query->page, $query->perPage);
    }
}
