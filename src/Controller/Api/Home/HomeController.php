<?php

declare(strict_types=1);

namespace App\Controller\Api\Home;

use App\Dto\Api\Home\HomeResponse;
use App\Repository\FileRepository;
use App\Repository\GistRepository;
use App\Repository\GuestbookRepository;
use App\Repository\NewsRepository;
use App\Repository\OnlineRepository;
use App\Service\ForumStats;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/home', name: 'api_home_')]
#[OA\Tag(name: 'Home')]
final class HomeController extends AbstractController
{
    #[Route('', name: 'show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Home page counters',
        content: new Model(type: HomeResponse::class),
    )]
    public function show(
        FileRepository $fileRepository,
        GistRepository $gistRepository,
        GuestbookRepository $guestbookRepository,
        NewsRepository $newsRepository,
        OnlineRepository $onlineRepository,
        ForumStats $forumStats,
    ): JsonResponse {
        $lastNews = $newsRepository->getLastNews();

        return $this->json(new HomeResponse(
            $fileRepository->countAll(),
            $gistRepository->countUser(),
            $guestbookRepository->countAll(),
            $onlineRepository->countAll(),
            null !== $lastNews?->getCreatedAt() ? \DateTimeImmutable::createFromInterface($lastNews->getCreatedAt()) : null,
            $forumStats->getTopicsCount(),
            $forumStats->getPostsCount(),
        ));
    }
}
