<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/health', name: 'api_health', methods: ['GET'])]
#[OA\Tag(name: 'System')]
final class HealthController
{
    #[OA\Response(
        response: 200,
        description: 'Service status',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'status', type: 'string', example: 'ok'),
            new OA\Property(property: 'symfony', type: 'string', example: '8.1.10'),
            new OA\Property(property: 'time', type: 'string', format: 'date-time'),
        ]),
    )]
    #[OA\Response(response: 503, description: 'Database unavailable')]
    public function __invoke(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');
        } catch (\Throwable) {
            return new JsonResponse(['status' => 'error'], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse([
            'status' => 'ok',
            'symfony' => Kernel::VERSION,
            'time' => new \DateTimeImmutable()->format(\DATE_ATOM),
        ]);
    }
}
