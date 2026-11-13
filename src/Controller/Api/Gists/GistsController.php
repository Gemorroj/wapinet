<?php

declare(strict_types=1);

namespace App\Controller\Api\Gists;

use App\Dto\Api\Gist\GistCreateRequest;
use App\Dto\Api\Gist\GistListQuery;
use App\Dto\Api\Gist\GistListResponse;
use App\Dto\Api\Gist\GistResponse;
use App\Dto\Api\Gist\GistUpdateRequest;
use App\Entity\Gist;
use App\Entity\User;
use App\Repository\GistRepository;
use App\Repository\UserRepository;
use App\Security\GistVoter;
use App\Service\Api\Gist\GistPresenter;
use App\Service\Api\Gist\GistSearch;
use App\Service\Api\Gist\GistService;
use App\Service\Paginate;
use App\Service\Search\SearchUnavailableException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/gists', name: 'api_gists_')]
#[OA\Tag(name: 'Gists')]
final class GistsController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of blog posts',
        content: new Model(type: GistListResponse::class),
    )]
    #[OA\Response(response: 503, description: 'Search engine is unavailable')]
    public function list(
        #[MapQueryString]
        GistListQuery $query,
        GistRepository $gistRepository,
        UserRepository $userRepository,
        Paginate $paginate,
        GistPresenter $presenter,
        GistSearch $gistSearch,
    ): JsonResponse {
        if ($gistSearch->supports($query)) {
            try {
                return $this->json($gistSearch->search($query));
            } catch (SearchUnavailableException $exception) {
                throw new ServiceUnavailableHttpException(null, 'Search engine is unavailable.', $exception);
            }
        }

        $user = null;

        if (null !== $query->filter->user && '' !== \trim($query->filter->user)) {
            $user = $userRepository->loadUserByIdentifier(\trim($query->filter->user));

            if (null === $user) {
                return $this->json(new GistListResponse([], 0, $query->page, $query->perPage));
            }
        }

        $pager = $paginate->paginate(
            $gistRepository->getApiListQueryBuilder($user, $query->sort),
            $query->page,
            $query->perPage,
        );

        $items = [];

        /** @var Gist $gist */
        foreach ($pager->getCurrentPageResults() as $gist) {
            $items[] = $presenter->toListItem($gist);
        }

        return $this->json(new GistListResponse(
            $items,
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Blog post',
        content: new Model(type: GistResponse::class),
    )]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    public function show(
        #[MapEntity(id: 'id', message: 'Сообщение не найдено.')]
        Gist $gist,
        GistPresenter $presenter,
    ): JsonResponse {
        return $this->json($presenter->toResponse($gist));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: GistCreateRequest::class)),
    )]
    #[OA\Response(
        response: 201,
        description: 'Created blog post',
        content: new Model(type: GistResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Spam detected')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 429, description: 'Too many blog posts')]
    public function create(
        #[MapRequestPayload]
        GistCreateRequest $payload,
        Request $request,
        #[CurrentUser]
        User $user,
        GistService $gistService,
        GistPresenter $presenter,
    ): JsonResponse {
        $gist = $gistService->create($payload, $request, $user);

        return $this->json($presenter->toResponse($gist), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[IsGranted(GistVoter::EDIT, subject: 'gist')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: GistUpdateRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Updated blog post',
        content: new Model(type: GistResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Access denied')]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(
        #[MapEntity(id: 'id', message: 'Сообщение не найдено.')]
        Gist $gist,
        #[MapRequestPayload]
        GistUpdateRequest $payload,
        GistService $gistService,
        GistPresenter $presenter,
    ): JsonResponse {
        $gistService->update($gist, $payload);

        return $this->json($presenter->toResponse($gist));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted(GistVoter::DELETE, subject: 'gist')]
    #[OA\Response(response: 204, description: 'Blog post deleted')]
    #[OA\Response(response: 403, description: 'Access denied')]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    public function delete(
        #[MapEntity(id: 'id', message: 'Сообщение не найдено.')]
        Gist $gist,
        GistService $gistService,
    ): Response {
        $gistService->delete($gist);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
