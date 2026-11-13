<?php

declare(strict_types=1);

namespace App\Service\Api\Gist;

use App\Dto\Api\Gist\GistListQuery;
use App\Dto\Api\Gist\GistListResponse;
use App\Repository\GistRepository;
use App\Repository\UserRepository;
use App\Service\Search\SearchEngineInterface;
use App\Service\Search\SearchRequest;

final readonly class GistSearch
{
    private const string INDEX = 'gist';

    public function __construct(
        private SearchEngineInterface $engine,
        private GistRepository $gistRepository,
        private UserRepository $userRepository,
        private GistPresenter $presenter,
    ) {
    }

    public function supports(GistListQuery $query): bool
    {
        return null !== $query->filter->q && '' !== \trim($query->filter->q);
    }

    public function search(GistListQuery $query): GistListResponse
    {
        $filters = [];

        if (null !== $query->filter->user && '' !== \trim($query->filter->user)) {
            $user = $this->userRepository->loadUserByIdentifier(\trim($query->filter->user));

            if (null === $user) {
                return $this->empty($query);
            }

            $filters[] = ['equals' => ['user_id' => (int) $user->getId()]];
        }

        $result = $this->engine->search(new SearchRequest(
            self::INDEX,
            \trim((string) $query->filter->q),
            $filters,
            $this->sort($query->sort),
            $query->page,
            $query->perPage,
        ));

        $gists = $this->gistRepository->findByIds($result->ids);

        return new GistListResponse(
            \array_map($this->presenter->toListItem(...), $gists),
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
            'relevance' => [['_score' => 'desc'], ['id' => 'desc']],
            default => [['created_at_ts' => 'desc'], ['id' => 'desc']],
        };
    }

    private function empty(GistListQuery $query): GistListResponse
    {
        return new GistListResponse([], 0, $query->page, $query->perPage);
    }
}
