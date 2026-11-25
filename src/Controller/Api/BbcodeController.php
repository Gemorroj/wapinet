<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimitByVisitor;
use App\Dto\Api\Bbcode\BbcodePreviewRequest;
use App\Dto\Api\Bbcode\BbcodePreviewResponse;
use App\Entity\User;
use App\Service\Bbcode;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/bbcode', name: 'api_bbcode_')]
#[OA\Tag(name: 'BBCode')]
final class BbcodeController extends AbstractController
{
    #[Route('/preview', name: 'preview', methods: ['POST'])]
    #[RateLimitByVisitor('api_bbcode_preview')]
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
        #[CurrentUser]
        ?User $user,
        Bbcode $bbcode,
    ): JsonResponse {
        return $this->json(new BbcodePreviewResponse($bbcode->render($payload->text)));
    }
}
