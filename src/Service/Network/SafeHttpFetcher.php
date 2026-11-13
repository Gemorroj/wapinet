<?php

declare(strict_types=1);

namespace App\Service\Network;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class SafeHttpFetcher
{
    private const int MAX_REDIRECTS = 3;

    public function __construct(
        private HttpClientInterface $httpClient,
        private OutboundUrlGuard $guard,
    ) {
    }

    public function get(string $url, int $timeout = 10, int $maxDuration = 60): ResponseInterface
    {
        for ($redirect = 0; $redirect <= self::MAX_REDIRECTS; ++$redirect) {
            $target = $this->guard->resolve($url);

            $options = [
                'max_redirects' => 0,
                'proxy' => '',
                'timeout' => $timeout,
                'max_duration' => $maxDuration,
                'buffer' => false,
            ];

            if ($target->host !== $target->ip) {
                $options['resolve'] = [$target->host => $target->ip];
            }

            $response = $this->httpClient->request('GET', $url, $options);
            $status = $response->getStatusCode();

            if ($status < 300 || $status >= 400) {
                return $response;
            }

            $location = $response->getHeaders(false)['location'][0] ?? null;

            if (null === $location) {
                return $response;
            }

            $response->cancel();
            $url = $this->absoluteUrl($location, $url);
        }

        throw new OutboundUrlBlockedException('Слишком много редиректов.');
    }

    /**
     * @return \Generator<int, string>
     */
    public function stream(ResponseInterface $response): \Generator
    {
        foreach ($this->httpClient->stream($response) as $chunk) {
            if ($chunk->isTimeout()) {
                throw new \RuntimeException('Превышено время ожидания при загрузке.');
            }

            yield $chunk->getContent();
        }
    }

    private function absoluteUrl(string $location, string $base): string
    {
        if (1 === \preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = \parse_url($base);

        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            return $location;
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        if ('//' === \substr($location, 0, 2)) {
            return $parts['scheme'].':'.$location;
        }

        if ('/' === ($location[0] ?? '')) {
            return $origin.$location;
        }

        if ('?' === ($location[0] ?? '')) {
            return $origin.($parts['path'] ?? '/').$location;
        }

        $path = $parts['path'] ?? '/';
        $position = \strrpos($path, '/');
        $directory = false === $position ? '/' : \substr($path, 0, $position + 1);

        return $origin.$directory.$location;
    }
}
