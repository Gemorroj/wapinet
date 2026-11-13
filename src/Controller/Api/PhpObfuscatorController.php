<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Obfuscator\ObfuscatorInfoResponse;
use App\Dto\Api\Obfuscator\ObfuscatorRequest;
use App\Dto\Api\Obfuscator\ObfuscatorResponse;
use App\Service\Api\CodeSourceException;
use App\Service\Api\CodeSourceLoader;
use App\Service\Api\Obfuscator\ObfuscatorException;
use App\Service\Api\Obfuscator\ObfuscatorRunner;
use App\Service\Api\RateLimit;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/php-obfuscator', name: 'api_php_obfuscator_')]
final class PhpObfuscatorController extends AbstractController
{
    #[Route('', name: 'info', methods: ['GET'])]
    #[Cache(public: true, maxage: 3600)]
    #[OA\Response(
        response: 200,
        description: 'Supported PHP version and maximum code size',
        content: new Model(type: ObfuscatorInfoResponse::class),
    )]
    public function info(): JsonResponse
    {
        return $this->json(new ObfuscatorInfoResponse(\PHP_VERSION, ObfuscatorRequest::MAX_CODE_SIZE));
    }

    #[Route('', name: 'obfuscate', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: ObfuscatorRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Obfuscated PHP code',
        content: new Model(type: ObfuscatorResponse::class),
    )]
    #[OA\Response(response: 422, description: 'PHP syntax error or validation error')]
    #[OA\Response(response: 429, description: 'Too many obfuscation requests')]
    #[OA\Response(response: 502, description: 'Obfuscated code could not be verified')]
    public function obfuscate(
        #[MapRequestPayload]
        ObfuscatorRequest $payload,
        Request $request,
        ObfuscatorRunner $runner,
        CodeSourceLoader $sourceLoader,
        #[Autowire(service: 'limiter.api_obfuscator_run')]
        RateLimiterFactory $obfuscatorLimiter,
    ): JsonResponse {
        RateLimit::consumeForIp($obfuscatorLimiter, $request);

        try {
            $code = $sourceLoader->load($payload->code, $payload->url, ObfuscatorRequest::MAX_CODE_SIZE);
            $result = $runner->run($code, $payload->removeComments, $payload->level);

            return $this->json(new ObfuscatorResponse($result, \strlen($code), \strlen($result)));
        } catch (CodeSourceException|ObfuscatorException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        } catch (\Exception $exception) {
            throw new HttpException(Response::HTTP_BAD_GATEWAY, 'Не удалось проверить результат обфускации.', $exception);
        }
    }
}
