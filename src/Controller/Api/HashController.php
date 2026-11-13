<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Hash\HashRequest;
use App\Dto\Api\Hash\HashResponse;
use App\Service\Api\RateLimit;
use App\Service\Api\Hash\UrlHasher;
use App\Service\FileSystem\RemoteFileException;
use App\Service\Network\OutboundUrlBlockedException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/hash', name: 'api_hash_')]
final class HashController extends AbstractController
{
    #[Route('', name: 'compute', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: HashRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Hashes of the remote file',
        content: new Model(type: HashResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error or blocked URL')]
    #[OA\Response(response: 429, description: 'Too many hash requests')]
    #[OA\Response(response: 502, description: 'The remote file is unavailable')]
    public function compute(
        #[MapRequestPayload]
        HashRequest $payload,
        Request $request,
        UrlHasher $hasher,
        #[Autowire(service: 'limiter.api_hash_compute')]
        RateLimiterFactory $hashLimiter,
    ): JsonResponse {
        RateLimit::consumeForIp($hashLimiter, $request);

        try {
            return $this->json($hasher->hash($payload->url, $payload->algorithms));
        } catch (OutboundUrlBlockedException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        } catch (RemoteFileException $exception) {
            throw new HttpException(Response::HTTP_BAD_GATEWAY, $exception->getMessage(), $exception);
        }
    }
}




