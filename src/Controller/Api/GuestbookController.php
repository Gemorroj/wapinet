<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimitByIp;
use App\Attribute\RateLimitByVisitor;
use App\Dto\Api\Guestbook\GuestbookCreateRequest;
use App\Dto\Api\Guestbook\GuestbookListResponse;
use App\Dto\Api\Guestbook\GuestbookMessageResponse;
use App\Dto\Api\Network\IpInsightResponse;
use App\Entity\Guestbook;
use App\Entity\User;
use App\Exception\PublicHttpException;
use App\Repository\GuestbookRepository;
use App\Service\Api\Guestbook\GuestbookPresenter;
use App\Service\Api\Guestbook\GuestbookService;
use App\Service\Paginate;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/guestbook', name: 'api_guestbook_')]
#[OA\Tag(name: 'Guestbook')]
final class GuestbookController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1))]
    #[OA\Response(
        response: 200,
        description: 'Paginated guestbook messages',
        content: new Model(type: GuestbookListResponse::class),
    )]
    public function list(
        Request $request,
        GuestbookRepository $guestbookRepository,
        Paginate $paginate,
        GuestbookPresenter $presenter,
    ): JsonResponse {
        $pager = $paginate->paginate($guestbookRepository->getListQuery(), \max(1, (int) $request->query->get('page', 1)));

        $items = [];

        /** @var Guestbook $message */
        foreach ($pager->getCurrentPageResults() as $message) {
            $items[] = $presenter->message($message);
        }

        return $this->json(new GuestbookListResponse(
            $items,
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }

    #[Route('/{id<\d+>}/insight', name: 'insight', methods: ['GET'])]
    #[RateLimitByIp('api_guestbook_insight')]
    #[OA\Response(
        response: 200,
        description: 'IP insight for an anonymous guestbook message',
        content: new Model(type: IpInsightResponse::class),
    )]
    #[OA\Response(response: 404, description: 'Message not found or message has an author')]
    #[OA\Response(response: 429, description: 'Too many insight requests')]
    public function insight(
        int $id,
        GuestbookRepository $guestbookRepository,
        GuestbookPresenter $presenter,
    ): JsonResponse {
        $message = $guestbookRepository->find($id);

        if (null === $message) {
            throw new PublicHttpException(404, 'Message not found.');
        }

        $insight = $presenter->insight($message);

        if (null === $insight) {
            throw new PublicHttpException(404, 'Insight is not available.');
        }

        return $this->json($insight);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[RateLimitByVisitor('api_guestbook_create')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: GuestbookCreateRequest::class)),
    )]
    #[OA\Response(
        response: 201,
        description: 'Created guestbook message',
        content: new Model(type: GuestbookMessageResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Captcha or spam verification failed')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 429, description: 'Too many messages')]
    public function create(
        #[MapRequestPayload]
        GuestbookCreateRequest $payload,
        Request $request,
        #[CurrentUser]
        ?User $user,
        GuestbookService $guestbookService,
        GuestbookPresenter $presenter,
    ): JsonResponse {
        return $this->json(
            $presenter->message($guestbookService->create($payload, $request, $user)),
            Response::HTTP_CREATED,
        );
    }
}
