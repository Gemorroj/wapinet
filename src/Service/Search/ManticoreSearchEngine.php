<?php

declare(strict_types=1);

namespace App\Service\Search;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsAlias(SearchEngineInterface::class)]
final readonly class ManticoreSearchEngine implements SearchEngineInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(MANTICORE_HOST)%')]
        private string $host,
        #[Autowire('%env(MANTICORE_HTTP_PORT)%')]
        private string $httpPort,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function status(): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->url().'/sql?mode=raw', [
                'body' => 'SHOW STATUS',
                'timeout' => 3,
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new SearchUnavailableException('Manticore returned an HTTP error.');
            }

            $data = $response->toArray(false);
        } catch (SearchUnavailableException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new SearchUnavailableException('Manticore is unavailable.', 0, $exception);
        }

        $status = [];

        foreach ($data[0]['data'] ?? [] as $row) {
            if (\is_array($row) && isset($row['Counter'], $row['Value'])) {
                $status[(string) $row['Counter']] = (string) $row['Value'];
            }
        }

        return $status;
    }

    public function search(SearchRequest $request): SearchResult
    {
        $query = [
            'bool' => [
                'must' => [
                    ['match' => ['*' => $request->text]],
                ],
            ],
        ];

        if ([] !== $request->filters) {
            $query['bool']['filter'] = $request->filters;
        }

        $body = [
            'index' => $request->index,
            'query' => $query,
            'limit' => $request->perPage,
            'offset' => \max(0, ($request->page - 1) * $request->perPage),
            'options' => [
                'ranker' => 'bm25',
            ],
        ];

        if ([] !== $request->sort) {
            $body['sort'] = $request->sort;
        }

        try {
            $response = $this->httpClient->request('POST', $this->url().'/search', [
                'json' => $body,
                'timeout' => 3,
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new SearchUnavailableException('Manticore returned an HTTP error.');
            }

            $data = $response->toArray(false);
        } catch (SearchUnavailableException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new SearchUnavailableException('Manticore is unavailable.', 0, $exception);
        }

        $hits = $data['hits']['hits'] ?? [];
        $total = $data['hits']['total'] ?? 0;

        if (\is_array($total)) {
            $total = $total['value'] ?? 0;
        }

        $ids = [];

        foreach ($hits as $hit) {
            if (\is_array($hit) && isset($hit['_id'])) {
                $ids[] = (int) $hit['_id'];
            }
        }

        return new SearchResult($ids, (int) $total);
    }

    private function url(): string
    {
        return \sprintf('http://%s:%s', $this->host, $this->httpPort);
    }
}
