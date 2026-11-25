<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimitByIp;
use App\Dto\Api\Validator\CssValidatorRequest;
use App\Dto\Api\Validator\ValidatorResponse;
use App\Exception\PublicHttpException;
use App\Service\Api\Validator\ValidatorRunnerInterface;
use App\Service\FileContent\SourceException;
use CSSValidator\Exception as CssValidatorException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/css-validator', name: 'api_css_validator_')]
final class CssValidatorController extends AbstractController
{
    #[Route('', name: 'validate', methods: ['POST'])]
    #[RateLimitByIp('api_css_validator_run')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CssValidatorRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'CSS validation result',
        content: new Model(type: ValidatorResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 429, description: 'Too many validation requests')]
    #[OA\Response(response: 502, description: 'W3C validator is unavailable')]
    public function validate(
        #[MapRequestPayload]
        CssValidatorRequest $payload,
        ValidatorRunnerInterface $runner,
    ): JsonResponse {
        try {
            return $this->json($runner->validateCss($payload->code, $payload->url, $payload->profile, $payload->warnings));
        } catch (SourceException $exception) {
            throw new PublicHttpException(422, $exception->getMessage(), $exception);
        } catch (CssValidatorException $exception) {
            throw new PublicHttpException(502, 'Сервис проверки W3C недоступен.', $exception);
        }
    }
}
