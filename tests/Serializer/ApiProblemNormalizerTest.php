<?php

declare(strict_types=1);

namespace App\Tests\Serializer;

use App\Exception\PublicHttpException;
use App\Serializer\ApiProblemNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;

final class ApiProblemNormalizerTest extends TestCase
{
    public function testUsesPublicMessageInProduction(): void
    {
        $exception = new PublicHttpException(409, 'Публичный текст.', logMessage: 'technical details');
        $data = $this->normalize($exception);

        self::assertSame('Публичный текст.', $data['detail']);
        self::assertSame(409, $data['status']);
    }

    public function testKeepsDefaultDetailForNonPublicException(): void
    {
        $exception = new BadRequestHttpException('technical details');
        $data = $this->normalize($exception);

        self::assertSame('Bad Request', $data['detail']);
    }

    public function testAddsPublicMessageForRateLimit(): void
    {
        $exception = new TooManyRequestsHttpException(30);
        $data = $this->normalize($exception);

        self::assertSame('Слишком много запросов. Повторите через 30 с.', $data['detail']);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(\Throwable $exception): array
    {
        $normalizer = new ApiProblemNormalizer(new ProblemNormalizer(false));
        $flatten = FlattenException::createFromThrowable($exception);

        return $normalizer->normalize($flatten, 'json', ['exception' => $exception, 'debug' => false]);
    }
}
