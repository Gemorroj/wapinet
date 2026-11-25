<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimitByIp;
use App\Dto\Api\Http\HttpCallRequest;
use App\Dto\Api\Http\HttpCallResponse;
use App\Service\Api\Http\HttpRequester;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/http', name: 'api_http_')]
final class HttpController extends AbstractController
{
    #[Route('', name: 'send', methods: ['POST'])]
    #[RateLimitByIp('api_http_send')]
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
        HttpRequester $requester,
    ): JsonResponse {
        return $this->json($requester->send($payload));
    }
}
