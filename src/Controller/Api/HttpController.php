<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Http\HttpCallRequest;
use App\Dto\Api\Http\HttpCallResponse;
use App\Service\Api\Http\HttpRequester;
use App\Service\Api\RateLimit;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/http', name: 'api_http_')]
final class HttpController extends AbstractController
{
    #[Route('', name: 'send', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: HttpCallRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Executed HTTP request and received response',
        content: new Model(type: HttpCallResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error or blocked destination')]
    #[OA\Response(response: 429, description: 'Too many HTTP requests')]
    public function send(
        #[MapRequestPayload]
        HttpCallRequest $payload,
        Request $request,
        HttpRequester $requester,
        #[Autowire(service: 'limiter.api_http_send')]
        RateLimiterFactory $httpLimiter,
    ): JsonResponse {
        RateLimit::consumeForIp($httpLimiter, $request);

        return $this->json($requester->send($payload));
    }
}
