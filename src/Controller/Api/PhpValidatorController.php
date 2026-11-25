<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimitByIp;
use App\Dto\Api\Validator\PhpValidatorInfoResponse;
use App\Dto\Api\Validator\ValidatorRequest;
use App\Dto\Api\Validator\ValidatorResponse;
use App\Exception\PublicHttpException;
use App\Service\Api\Validator\ValidatorRunnerInterface;
use App\Service\FileContent\SourceException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/php-validator', name: 'api_php_validator_')]
final class PhpValidatorController extends AbstractController
{
    #[Route('', name: 'info', methods: ['GET'])]
    #[Cache(public: true, maxage: 3600)]
    #[OA\Response(
        response: 200,
        description: 'Supported PHP version',
        content: new Model(type: PhpValidatorInfoResponse::class),
    )]
    public function info(): JsonResponse
    {
        return $this->json(new PhpValidatorInfoResponse(\PHP_VERSION));
    }

    #[Route('', name: 'validate', methods: ['POST'])]
    #[RateLimitByIp('api_php_validator_run')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: ValidatorRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'PHP syntax check result',
        content: new Model(type: ValidatorResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 429, description: 'Too many validation requests')]
    #[OA\Response(response: 502, description: 'PHP CLI is unavailable')]
    public function validate(
        #[MapRequestPayload]
        ValidatorRequest $payload,
        ValidatorRunnerInterface $runner,
    ): JsonResponse {
        try {
            return $this->json($runner->validatePhp($payload->code, $payload->url));
        } catch (SourceException $exception) {
            throw new PublicHttpException(422, $exception->getMessage(), $exception);
        } catch (\Exception $exception) {
            throw new PublicHttpException(502, 'Не удалось проверить PHP-код.', $exception);
        }
    }
}
