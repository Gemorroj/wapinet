<?php

declare(strict_types=1);

namespace App\Service\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds RFC 9457 problem+json responses outside the error renderer
 * (security entry points, access denied handlers, login failures).
 */
final readonly class ProblemFactory
{
    /**
     * @param array<string, string> $headers
     */
    public function response(int $status, string $detail, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'status' => $status,
            'detail' => $detail,
        ], $status, ['Content-Type' => 'application/problem+json'] + $headers);
    }
}
