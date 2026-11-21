<?php

declare(strict_types=1);

namespace App\Service\Api\Http;

use App\Dto\Api\Http\HttpCallHeader;
use App\Dto\Api\Http\HttpCallRequest;
use App\Dto\Api\Http\HttpCallResponse;
use App\Service\Network\OutboundUrlBlockedException;
use App\Service\Network\OutboundUrlGuard;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class HttpRequester
{
    private const int MAX_BODY = 2 * 1024 * 1024;
    private const int MAX_HEADER_COUNT = 50;
    private const int MAX_HEADER_SIZE = 16384;
    private const array FORBIDDEN_HEADERS = ['content-length', 'transfer-encoding'];

    public function __construct(
        private HttpClientInterface $httpClient,
        private OutboundUrlGuard $guard,
    ) {
    }

    public function send(HttpCallRequest $request): HttpCallResponse
    {
        try {
            $target = $this->guard->resolve($request->url);
        } catch (OutboundUrlBlockedException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }

        $timeout = \max(1, \min(30, $request->timeout));
        $options = [
            'headers' => $this->headers($request->headers),
            'max_redirects' => 0,
            'proxy' => '',
            'timeout' => $timeout,
            'max_duration' => $timeout + 5,
            'buffer' => false,
            'http_version' => match ($request->httpVersion) {
                '1.1' => '1.1',
                '2' => '2.0',
                '3' => '3.0',
                default => null,
            },
        ];

        if (!$request->verifyTls) {
            $options['verify_peer'] = false;
            $options['verify_host'] = false;
        }

        if ($target->host !== $target->ip) {
            $options['resolve'] = [$target->host => $target->ip];
        }
        $body = $this->body($request);

        if (null !== $body) {
            $options['body'] = $body;
        }

        $start = \hrtime(true);

        try {
            $response = $this->httpClient->request($this->method($request->method), $request->url, $options);
            $status = $response->getStatusCode();
            $responseHeaders = $response->getHeaders(false);
            $httpVersion = $response->getInfo('http_version');
            [$content, $truncated] = $this->read($response);
        } catch (TransportExceptionInterface $exception) {
            throw new UnprocessableEntityHttpException($this->transportError($exception), $exception);
        }

        $time = (\hrtime(true) - $start) / 1e6;
        $contentType = $responseHeaders['content-type'][0] ?? null;
        $text = $this->text($content, $contentType);
        $isBinary = $this->isBinary($text, $contentType);

        return new HttpCallResponse(
            status: $status,
            statusText: Response::$statusTexts[$status] ?? '',
            httpVersion: $this->httpVersion($httpVersion),
            url: $request->url,
            location: $responseHeaders['location'][0] ?? null,
            time: \round($time, 1),
            size: \strlen($content),
            truncated: $truncated,
            contentType: $contentType,
            bodyLanguage: $this->language($contentType),
            binary: $isBinary,
            body: $isBinary ? null : $text,
            bodyBase64: $isBinary ? \base64_encode($content) : null,
            headers: $this->responseHeaders($responseHeaders),
        );
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function read(ResponseInterface $response): array
    {
        $content = '';
        $truncated = false;

        foreach ($this->httpClient->stream($response) as $chunk) {
            if ($chunk->isTimeout()) {
                $truncated = true;
                $response->cancel();

                break;
            }

            $content .= $chunk->getContent();

            if (\strlen($content) > self::MAX_BODY) {
                $content = \substr($content, 0, self::MAX_BODY);
                $truncated = true;
                $response->cancel();

                break;
            }
        }

        return [$content, $truncated];
    }

    private function method(string $method): string
    {
        $method = \strtoupper(\trim($method));

        if ('' === $method) {
            return 'GET';
        }

        if (1 !== \preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]{1,32}$/', $method)) {
            throw new UnprocessableEntityHttpException('Некорректный HTTP-метод.');
        }

        if ('CONNECT' === $method) {
            throw new UnprocessableEntityHttpException('Метод CONNECT не поддерживается: HTTP-клиент не устанавливает туннель.');
        }

        return $method;
    }

    /**
     * @return array<string, list<string>>
     */
    private function headers(?string $headers): array
    {
        if (null === $headers || '' === \trim($headers)) {
            return [];
        }

        $result = [];
        $size = 0;

        foreach (\preg_split('/\r\n|\n|\r/', $headers) ?: [] as $line) {
            $line = \trim($line);

            if ('' === $line) {
                continue;
            }

            $position = \strpos($line, ':');

            if (false === $position) {
                throw new UnprocessableEntityHttpException('Некорректная строка заголовка: '.$line);
            }

            $name = \trim(\substr($line, 0, $position));
            $value = \trim(\substr($line, $position + 1));

            if (1 !== \preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/', $name)) {
                // Имя заголовка — token по RFC 9110. Иначе curl падает с ошибкой framing,
                // поэтому проверяем заранее и отдаём понятный 422.
                throw new UnprocessableEntityHttpException('Некорректное имя заголовка: '.$name);
            }

            if (1 === \preg_match('/[\r\n]/', $value)) {
                throw new UnprocessableEntityHttpException('Некорректное значение заголовка: '.$name);
            }

            $key = \strtolower($name);

            if (\in_array($key, self::FORBIDDEN_HEADERS, true)) {
                continue;
            }

            $result[$key][] = $value;
            $size += \strlen($name) + \strlen($value);

            if (\count($result) > self::MAX_HEADER_COUNT || $size > self::MAX_HEADER_SIZE) {
                throw new UnprocessableEntityHttpException('Слишком много заголовков.');
            }
        }

        return $result;
    }

    private function body(HttpCallRequest $request): ?string
    {
        if (null === $request->body || '' === $request->body) {
            return null;
        }

        if ('base64' !== $request->bodyEncoding) {
            return $request->body;
        }

        $body = \base64_decode($request->body, true);

        if (false === $body) {
            throw new UnprocessableEntityHttpException('Тело не является корректным base64.');
        }

        return $body;
    }

    private function text(string $content, ?string $contentType): string
    {
        if (null !== $contentType && 1 === \preg_match('/charset=([^;]+)/i', $contentType, $matches)) {
            $charset = \trim($matches[1], " \t\"'");

            if (!\in_array(\strtolower($charset), ['utf-8', 'utf8'], true)) {
                try {
                    return \mb_convert_encoding($content, 'UTF-8', $charset);
                } catch (\Throwable) {
                    return $content;
                }
            }
        }

        return $content;
    }

    private function isBinary(string $content, ?string $contentType): bool
    {
        if (!\mb_check_encoding($content, 'UTF-8')) {
            return true;
        }

        if ($this->isTextual($contentType)) {
            return false;
        }

        return 1 === \preg_match('/[\x00-\x08\x0E-\x1F]/', $content);
    }

    private function isTextual(?string $contentType): bool
    {
        $mime = $this->mimeType($contentType);

        if (null === $mime || '' === $mime) {
            return false;
        }

        return \str_starts_with($mime, 'text/')
            || \in_array($mime, [
                'application/json',
                'application/ld+json',
                'application/xml',
                'application/xhtml+xml',
                'application/javascript',
                'application/x-javascript',
                'application/ecmascript',
                'application/x-www-form-urlencoded',
                'application/graphql',
                'application/yaml',
                'application/x-yaml',
                'image/svg+xml',
            ], true)
            || \str_ends_with($mime, '+json')
            || \str_ends_with($mime, '+xml');
    }

    private function language(?string $contentType): ?string
    {
        $mime = $this->mimeType($contentType);

        if (null === $mime) {
            return null;
        }

        if (\in_array($mime, ['application/json', 'application/ld+json'], true) || \str_ends_with($mime, '+json')) {
            return 'json';
        }

        if (\in_array($mime, ['text/html', 'application/xhtml+xml'], true)) {
            return 'html';
        }

        if (\in_array($mime, ['application/xml', 'text/xml', 'image/svg+xml'], true) || \str_ends_with($mime, '+xml')) {
            return 'xml';
        }

        return null;
    }

    private function mimeType(?string $contentType): ?string
    {
        if (null === $contentType) {
            return null;
        }

        return \strtolower(\trim(\explode(';', $contentType)[0]));
    }

    /**
     * @param array<string, list<string>> $headers
     *
     * @return list<HttpCallHeader>
     */
    private function responseHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $result[] = new HttpCallHeader($name, $value);
            }
        }

        return $result;
    }

    private function transportError(TransportExceptionInterface $exception): string
    {
        $message = \trim($exception->getMessage());

        if (\mb_strlen($message) > 300) {
            $message = \mb_substr($message, 0, 300).'…';
        }

        return '' === $message ? 'Не удалось выполнить запрос.' : 'Не удалось выполнить запрос: '.$message;
    }

    private function httpVersion(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_string($value) && !\is_numeric($value)) {
            return $value;
        }

        $code = (int) $value;

        return match (true) {
            1 === $code => '1.0',
            2 === $code => '1.1',
            \in_array($code, [3, 4, 5], true) => '2',
            $code >= 30 => '3',
            default => null,
        };
    }
}
