<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * HTTP error with an explicit public message.
 *
 * The public message is rendered in problem+json. getMessage() is used for logs
 * only; when no log message is given it defaults to the public message.
 */
class PublicHttpException extends HttpException implements PublicErrorInterface
{
    public function __construct(
        int $statusCode,
        private readonly string $publicMessage,
        ?\Throwable $previous = null,
        array $headers = [],
        int $code = 0,
        string $logMessage = '',
    ) {
        parent::__construct($statusCode, '' !== $logMessage ? $logMessage : $publicMessage, $previous, $headers, $code);
    }

    public function getPublicMessage(): string
    {
        return $this->publicMessage;
    }

    public static function unauthorized(string $challenge, string $publicMessage, ?\Throwable $previous = null): self
    {
        return new self(401, $publicMessage, $previous, ['WWW-Authenticate' => $challenge]);
    }
}
