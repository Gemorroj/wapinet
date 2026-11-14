<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Tag\TagListItem;
use App\Dto\Api\Tag\TagListQuery;
use App\Dto\Api\Tag\TagListResponse;
use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Service\Paginate;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tags', name: 'api_tags_')]
#[OA\Tag(name: 'Tags')]
final class TagsController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of tags',
        content: new Model(type: TagListResponse::class),
    )]
    public function list(
        #[MapQueryString]
        TagListQuery $query,
        TagRepository $tagRepository,
        Paginate $paginate,
    ): JsonResponse {
        $pager = $paginate->paginate(
            $tagRepository->getApiListQueryBuilder($query->filter->q),
            $query->page,
            $query->perPage,
        );

        $items = [];

        /** @var Tag $tag */
        foreach ($pager->getCurrentPageResults() as $tag) {
            $items[] = new TagListItem((int) $tag->getId(), $tag->getName(), $tag->getCount());
        }

        return $this->json(new TagListResponse(
            $items,
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }
}
