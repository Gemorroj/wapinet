<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Bbcode\BbcodePreviewRequest;
use App\Dto\Api\Bbcode\BbcodePreviewResponse;
use App\Entity\User;
use App\Service\Api\RateLimit;
use App\Service\Bbcode;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/bbcode', name: 'api_bbcode_')]
#[OA\Tag(name: 'BBCode')]
final class BbcodeController extends AbstractController
{
    #[Route('/preview', name: 'preview', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: BbcodePreviewRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Rendered BBCode',
        content: new Model(type: BbcodePreviewResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 429, description: 'Too many preview requests')]
    public function preview(
        #[MapRequestPayload]
        BbcodePreviewRequest $payload,
        Request $request,
        Bbcode $bbcode,
        #[Autowire(service: 'limiter.api_bbcode_preview')]
        RateLimiterFactory $previewLimiter,
    ): JsonResponse {
        $user = $this->getUser();

        RateLimit::consumeForVisitor($previewLimiter, $request, $user instanceof User ? $user : null);

        return $this->json(new BbcodePreviewResponse($bbcode->render($payload->text)));
    }
}
