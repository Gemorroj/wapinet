<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\Service\Search\SearchEngineInterface;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchResult;

final class SearchEngineStub implements SearchEngineInterface
{
    public function search(SearchRequest $request): SearchResult
    {
        return new SearchResult([], 0);
    }
}
