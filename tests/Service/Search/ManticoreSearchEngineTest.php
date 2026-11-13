<?php

declare(strict_types=1);

namespace App\Tests\Service\Search;

use App\Service\Search\ManticoreSearchEngine;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchUnavailableException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreSearchEngineTest extends TestCase
{
    public function testSearchWithFiltersAndSort(): void
    {
        $engine = new ManticoreSearchEngine(
            new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
                self::assertSame('POST', $method);
                self::assertSame('http://manticore:9308/search', $url);

                $body = \json_decode($options['body'], true, 512, \JSON_THROW_ON_ERROR);
                self::assertSame('files', $body['index']);
                self::assertSame([
                    'bool' => [
                        'must' => [
                            ['match' => ['*' => 'hello']],
                        ],
                        'filter' => [
                            ['equals' => ['user_id' => 5]],
                            ['range' => ['created_at_ts' => ['gte' => 100, 'lt' => 200]]],
                        ],
                    ],
                ], $body['query']);
                self::assertSame(20, $body['limit']);
                self::assertSame(20, $body['offset']);
                self::assertSame([['count_views' => 'desc'], ['id' => 'desc']], $body['sort']);

                return new MockResponse('{"hits":{"total":2,"hits":[{"_id":"5"},{"_id":"7"}]}}');
            }),
            'manticore',
            '9308',
        );

        $result = $engine->search(new SearchRequest(
            'files',
            'hello',
            [
                ['equals' => ['user_id' => 5]],
                ['range' => ['created_at_ts' => ['gte' => 100, 'lt' => 200]]],
            ],
            [['count_views' => 'desc'], ['id' => 'desc']],
            2,
            20,
        ));

        self::assertSame([5, 7], $result->ids);
        self::assertSame(2, $result->total);
    }

    public function testSearchWithoutFiltersOmitsFilterClause(): void
    {
        $engine = new ManticoreSearchEngine(
            new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
                $body = \json_decode($options['body'], true, 512, \JSON_THROW_ON_ERROR);
                self::assertArrayNotHasKey('filter', $body['query']['bool']);
                self::assertArrayNotHasKey('sort', $body);

                return new MockResponse('{"hits":{"total":0,"hits":[]}}');
            }),
            'manticore',
            '9308',
        );

        $result = $engine->search(new SearchRequest('files', 'hello'));

        self::assertSame([], $result->ids);
        self::assertSame(0, $result->total);
    }

    public function testStatusUsesRawSqlEndpoint(): void
    {
        $engine = new ManticoreSearchEngine(
            new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
                self::assertSame('POST', $method);
                self::assertSame('http://manticore:9308/sql?mode=raw', $url);
                self::assertSame('SHOW STATUS', $options['body']);

                return new MockResponse(<<<JSON
                    [{
                        "columns": [{"Counter": {"type": "string"}}, {"Value": {"type": "string"}}],
                        "data": [
                            {"Counter": "uptime", "Value": "42"},
                            {"Counter": "version", "Value": "29.9.0"}
                        ],
                        "total": 2,
                        "error": "",
                        "warning": ""
                    }]
                    JSON);
            }),
            'manticore',
            '9308',
        );

        self::assertSame(['uptime' => '42', 'version' => '29.9.0'], $engine->status());
    }

    public function testSearchUnavailable(): void
    {
        $engine = new ManticoreSearchEngine(
            new MockHttpClient(new MockResponse('', ['http_code' => 500])),
            'manticore',
            '9308',
        );

        $this->expectException(SearchUnavailableException::class);

        $engine->search(new SearchRequest('files', 'hello'));
    }
}
