<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimitByIp;
use App\Dto\Api\Whois\WhoisRequest;
use App\Dto\Api\Whois\WhoisResponse;
use App\Exception\PublicHttpException;
use App\Service\Api\Whois\WhoisException;
use App\Service\Api\Whois\WhoisLookup;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/whois', name: 'api_whois_')]
final class WhoisController extends AbstractController
{
    #[Route('', name: 'lookup', methods: ['POST'])]
    #[RateLimitByIp('api_whois_lookup')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: WhoisRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'WHOIS/RDAP lookup result',
        content: new Model(type: WhoisResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 429, description: 'Too many lookup requests')]
    #[OA\Response(response: 502, description: 'WHOIS/RDAP service is unavailable')]
    public function lookup(
        #[MapRequestPayload]
        WhoisRequest $payload,
        Request $request,
        WhoisLookup $lookup,
    ): JsonResponse {
        $query = \trim($payload->query);

        if ('' === $query) {
            $query = (string) $request->getClientIp();

            if ('' === $query) {
                throw new PublicHttpException(400, 'Cannot determine client IP.');
            }
        }

        try {
            return $this->json($lookup->lookup($query, $payload->protocol));
        } catch (WhoisException $exception) {
            throw new PublicHttpException(502, $exception->getMessage(), $exception);
        }
    }
}
