<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Validator\CssValidatorRequest;
use App\Dto\Api\Validator\ValidatorResponse;
use App\Service\Api\RateLimit;
use App\Service\Api\CodeSourceException;
use App\Service\Api\Validator\ValidatorRunnerInterface;
use CSSValidator\Exception as CssValidatorException;
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

#[Route('/api/css-validator', name: 'api_css_validator_')]
final class CssValidatorController extends AbstractController
{
    #[Route('', name: 'validate', methods: ['POST'])]
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
        Request $request,
        ValidatorRunnerInterface $runner,
        #[Autowire(service: 'limiter.api_css_validator_run')]
        RateLimiterFactory $limiter,
    ): JsonResponse {
        RateLimit::consumeForIp($limiter, $request);

        try {
            return $this->json($runner->validateCss($payload->code, $payload->url, $payload->profile, $payload->warnings));
        } catch (CodeSourceException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        } catch (CssValidatorException $exception) {
            throw new HttpException(Response::HTTP_BAD_GATEWAY, 'Сервис проверки W3C недоступен.', $exception);
        }
    }
}



