<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Online\OnlineItemResponse;
use App\Dto\Api\Online\OnlineListResponse;
use App\Repository\OnlineRepository;
use App\Service\OnlineTracker;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/online', name: 'api_online_')]
#[OA\Tag(name: 'Online')]
final class OnlineController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Current online sessions',
        content: new Model(type: OnlineListResponse::class),
    )]
    public function list(Request $request, OnlineRepository $onlineRepository, OnlineTracker $onlineTracker): JsonResponse
    {
        $onlineTracker->track($request);

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $items = [];

        foreach ($onlineRepository->getRecent() as $online) {
            $items[] = new OnlineItemResponse(
                (int) $online->getId(),
                \DateTimeImmutable::createFromInterface($online->getDatetime()),
                $online->getBrowser(),
                $isAdmin ? $online->getIp() : null,
                $isAdmin ? $online->getPath() : null,
            );
        }

        return $this->json(new OnlineListResponse($items, $onlineRepository->countAll()));
    }
}
