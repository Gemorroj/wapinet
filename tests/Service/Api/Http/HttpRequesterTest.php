<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Http;

use App\Dto\Api\Http\HttpCallRequest;
use App\Service\Api\Http\HttpRequester;
use App\Service\Network\DnsLookup;
use App\Service\Network\OutboundUrlGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class HttpRequesterTest extends TestCase
{
    public function testReturnsResponseData(): void
    {
        $client = new MockHttpClient(new MockResponse('hello', [
            'http_code' => 201,
            'response_headers' => [
                'content-type' => 'text/plain',
                'x-test' => 'yes',
            ],
        ]));

        $response = $this->requester($client)->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertSame(201, $response->status);
        static::assertSame('Created', $response->statusText);
        static::assertSame('hello', $response->body);
        static::assertFalse($response->binary);
        static::assertFalse($response->truncated);
        static::assertSame('text/plain', $response->contentType);
        static::assertNull($response->location);
        static::assertSame([['name' => 'content-type', 'value' => 'text/plain'], ['name' => 'x-test', 'value' => 'yes']], \array_map(
            static fn ($header): array => ['name' => $header->name, 'value' => $header->value],
            $response->headers,
        ));
    }

    public function testDoesNotFollowRedirects(): void
    {
        $client = new MockHttpClient(new MockResponse('', [
            'http_code' => 302,
            'response_headers' => ['location' => '/next'],
        ]));

        $response = $this->requester($client)->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertSame(302, $response->status);
        static::assertSame('/next', $response->location);
    }

    public function testTruncatesLargeBody(): void
    {
        $body = \str_repeat('a', 2 * 1024 * 1024 + 100);
        $client = new MockHttpClient(new MockResponse($body));

        $response = $this->requester($client)->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertTrue($response->truncated);
        static::assertSame(2 * 1024 * 1024, $response->size);
        static::assertSame(2 * 1024 * 1024, \strlen((string) $response->body));
    }

    public function testMarksBinaryBody(): void
    {
        $body = "\x00\x01\x02";
        $client = new MockHttpClient(new MockResponse($body, [
            'response_headers' => ['content-type' => 'application/octet-stream'],
        ]));

        $response = $this->requester($client)->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertTrue($response->binary);
        static::assertNull($response->body);
        static::assertSame(\base64_encode($body), $response->bodyBase64);
        static::assertNull($response->bodyLanguage);
    }

    public function testDetectsJsonLanguage(): void
    {
        $client = new MockHttpClient(new MockResponse('{"a":1}', [
            'response_headers' => ['content-type' => 'application/json'],
        ]));

        $response = $this->requester($client)->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertSame('json', $response->bodyLanguage);
        static::assertSame('{"a":1}', $response->body);
    }

    public function testConvertsNonUtf8Charset(): void
    {
        $body = \mb_convert_encoding('Привет', 'CP1251', 'UTF-8');
        $client = new MockHttpClient(new MockResponse($body, [
            'response_headers' => ['content-type' => 'text/plain; charset=windows-1251'],
        ]));

        $response = $this->requester($client)->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertFalse($response->binary);
        static::assertSame('Привет', $response->body);
    }

    public function testDetectsSuffixedJsonAndXmlMediaTypes(): void
    {
        $json = $this->requester(new MockHttpClient(new MockResponse('{"a":1}', [
            'response_headers' => ['content-type' => 'application/vnd.api+json'],
        ])))->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertSame('json', $json->bodyLanguage);
        static::assertFalse($json->binary);

        $xml = $this->requester(new MockHttpClient(new MockResponse('<feed/>', [
            'response_headers' => ['content-type' => 'application/atom+xml; charset=utf-8'],
        ])))->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

        static::assertSame('xml', $xml->bodyLanguage);
        static::assertFalse($xml->binary);
    }

    public function testPassesHttpVersionAndDisablesTlsVerification(): void
    {
        $captured = [];
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = $options;

            return new MockResponse('ok');
        });

        $this->requester($client)->send(new HttpCallRequest(
            url: 'http://8.8.8.8/',
            httpVersion: '2',
            verifyTls: false,
        ));

        static::assertSame('2.0', $captured['http_version']);
        static::assertFalse($captured['verify_peer']);
        static::assertFalse($captured['verify_host']);
    }

    public function testMapsCurlHttpVersionCodes(): void
    {
        foreach ([2 => '1.1', 3 => '2', 30 => '3'] as $code => $expected) {
            $client = new MockHttpClient(new MockResponse('ok', ['http_version' => $code]));
            $response = $this->requester($client)->send(new HttpCallRequest(url: 'http://8.8.8.8/'));

            static::assertSame($expected, $response->httpVersion);
        }
    }

    public function testStripsForbiddenHeadersAndDecodesBase64Body(): void
    {
        $captured = [];
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = ['method' => $method, 'options' => $options];

            return new MockResponse('ok');
        });

        $this->requester($client)->send(new HttpCallRequest(
            url: 'http://8.8.8.8/',
            method: 'post',
            headers: "Content-Length: 99\nX-Test: yes\nTransfer-Encoding: chunked",
            body: \base64_encode('raw'),
            bodyEncoding: 'base64',
        ));

        static::assertSame('POST', $captured['method']);
        static::assertSame('raw', $captured['options']['body']);
        static::assertSame('', $captured['options']['proxy']);
        static::assertSame(0, $captured['options']['max_redirects']);
        static::assertNotContains('Content-Length: 99', $captured['options']['headers']);
        static::assertNotContains('Transfer-Encoding: chunked', $captured['options']['headers']);
        static::assertContains('x-test: yes', $captured['options']['headers']);
        static::assertArrayNotHasKey('resolve', $captured['options']);
    }

    public function testRejectsPrivateUrl(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->requester(new MockHttpClient())->send(new HttpCallRequest(url: 'http://127.0.0.1/'));
    }

    public function testRejectsInvalidMethod(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->requester(new MockHttpClient())->send(new HttpCallRequest(url: 'http://8.8.8.8/', method: 'GE T'));
    }

    public function testRejectsConnectMethod(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->requester(new MockHttpClient())->send(new HttpCallRequest(url: 'http://8.8.8.8/', method: 'CONNECT'));
    }

    public function testRejectsMalformedHeader(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->requester(new MockHttpClient())->send(new HttpCallRequest(url: 'http://8.8.8.8/', headers: 'Bad header'));
    }

    public function testRejectsInvalidBase64Body(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->requester(new MockHttpClient())->send(new HttpCallRequest(url: 'http://8.8.8.8/', body: '!!!', bodyEncoding: 'base64'));
    }

    public function testRejectsHostThatResolvesToPrivateAddress(): void
    {
        $client = new MockHttpClient(static fn (string $method, string $url, array $options): MockResponse => new MockResponse(
            (string) \json_encode(['Status' => 0, 'Answer' => [['name' => 'internal.test', 'type' => 1, 'data' => '10.0.0.1']]]),
        ));

        $this->expectException(UnprocessableEntityHttpException::class);

        $this->requester($client)->send(new HttpCallRequest(url: 'http://internal.test/'));
    }

    private function requester(MockHttpClient $client): HttpRequester
    {
        return new HttpRequester($client, new OutboundUrlGuard(new DnsLookup($client, new ArrayAdapter())));
    }
}
