<?php

declare(strict_types=1);

namespace App\Service\Search;

interface SearchEngineInterface
{
    /**
     * @throws SearchUnavailableException
     */
    public function search(SearchRequest $request): SearchResult;
}
