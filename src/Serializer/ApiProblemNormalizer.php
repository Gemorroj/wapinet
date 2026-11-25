<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Exception\PublicErrorInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Adds the public message of PublicErrorInterface exceptions to problem+json,
 * including in non-debug mode where Symfony renders the status text instead.
 */
final class ApiProblemNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    public function __construct(
        private readonly ProblemNormalizer $inner,
    ) {
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $data = $this->inner->normalize($object, $format, $context);
        $exception = $context['exception'] ?? null;

        if ($exception instanceof \Throwable) {
            for ($e = $exception; null !== $e; $e = $e->getPrevious()) {
                if ($e instanceof PublicErrorInterface) {
                    $data['detail'] = $e->getPublicMessage();

                    break;
                }

                if ($e instanceof TooManyRequestsHttpException) {
                    $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 0);
                    $data['detail'] = 0 < $retryAfter
                        ? \sprintf('Слишком много запросов. Повторите через %d с.', $retryAfter)
                        : 'Слишком много запросов. Попробуйте позже.';

                    break;
                }
            }
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format, $context);
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->inner->setSerializer($serializer);
    }
}
