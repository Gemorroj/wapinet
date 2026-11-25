<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimitByIp;
use App\Dto\Api\Validator\ValidatorRequest;
use App\Dto\Api\Validator\ValidatorResponse;
use App\Exception\PublicHttpException;
use App\Service\Api\Validator\ValidatorRunnerInterface;
use App\Service\FileContent\SourceException;
use HTMLValidator\Exception as HtmlValidatorException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/html-validator', name: 'api_html_validator_')]
final class HtmlValidatorController extends AbstractController
{
    #[Route('', name: 'validate', methods: ['POST'])]
    #[RateLimitByIp('api_html_validator_run')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: ValidatorRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'HTML validation result',
        content: new Model(type: ValidatorResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 429, description: 'Too many validation requests')]
    #[OA\Response(response: 502, description: 'W3C validator is unavailable')]
    public function validate(
        #[MapRequestPayload]
        ValidatorRequest $payload,
        ValidatorRunnerInterface $runner,
    ): JsonResponse {
        try {
            return $this->json($runner->validateHtml($payload->code, $payload->url));
        } catch (SourceException $exception) {
            throw new PublicHttpException(422, $exception->getMessage(), $exception);
        } catch (HtmlValidatorException $exception) {
            throw new PublicHttpException(502, 'Сервис проверки W3C недоступен.', $exception);
        }
    }
}
