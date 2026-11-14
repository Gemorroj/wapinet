<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\News\NewsListResponse;
use App\Dto\Api\News\NewsResponse;
use App\Entity\News;
use App\Repository\NewsRepository;
use App\Service\Api\News\NewsPresenter;
use App\Service\Paginate;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/news', name: 'api_news_')]
#[OA\Tag(name: 'News')]
final class NewsController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1))]
    #[OA\Response(
        response: 200,
        description: 'Paginated news',
        content: new Model(type: NewsListResponse::class),
    )]
    public function list(
        Request $request,
        NewsRepository $newsRepository,
        Paginate $paginate,
        NewsPresenter $presenter,
    ): JsonResponse {
        $pager = $paginate->paginate($newsRepository->getAllBuilder(), \max(1, (int) $request->query->get('page', 1)));

        $items = [];

        /** @var News $news */
        foreach ($pager->getCurrentPageResults() as $news) {
            $items[] = $presenter->listItem($news);
        }

        return $this->json(new NewsListResponse(
            $items,
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'News item',
        content: new Model(type: NewsResponse::class),
    )]
    #[OA\Response(response: 404, description: 'News not found')]
    public function show(
        #[MapEntity(id: 'id', message: 'Новость не найдена.')]
        News $news,
        NewsPresenter $presenter,
    ): JsonResponse {
        return $this->json($presenter->news($news));
    }
}
