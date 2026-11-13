<?php

declare(strict_types=1);

namespace App\Controller\Api\Guestbook;

use App\Dto\Api\Guestbook\GuestbookCreateRequest;
use App\Dto\Api\Guestbook\GuestbookListResponse;
use App\Dto\Api\Guestbook\GuestbookMessageResponse;
use App\Dto\Api\Network\IpInsightResponse;
use App\Entity\Guestbook;
use App\Entity\User;
use App\Repository\GuestbookRepository;
use App\Service\Api\Guestbook\GuestbookPresenter;
use App\Service\Api\Guestbook\GuestbookService;
use App\Service\Api\RateLimit;
use App\Service\Paginate;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

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
    #[OA\Response(
        response: 200,
        description: 'IP insight for an anonymous guestbook message',
        content: new Model(type: IpInsightResponse::class),
    )]
    #[OA\Response(response: 404, description: 'Message not found or message has an author')]
    #[OA\Response(response: 429, description: 'Too many insight requests')]
    public function insight(
        int $id,
        Request $request,
        GuestbookRepository $guestbookRepository,
        GuestbookPresenter $presenter,
        #[Autowire(service: 'limiter.api_guestbook_insight')]
        RateLimiterFactory $insightLimiter,
    ): JsonResponse {
        RateLimit::consumeForIp($insightLimiter, $request);

        $message = $guestbookRepository->find($id);

        if (null === $message) {
            throw new NotFoundHttpException('Message not found.');
        }

        $insight = $presenter->insight($message);

        if (null === $insight) {
            throw new NotFoundHttpException('Insight is not available.');
        }

        return $this->json($insight);
    }

    #[Route('', name: 'create', methods: ['POST'])]
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
        GuestbookService $guestbookService,
        GuestbookPresenter $presenter,
    ): JsonResponse {
        $user = $this->getUser();
        $user = $user instanceof User ? $user : null;

        return $this->json(
            $presenter->message($guestbookService->create($payload, $request, $user)),
            Response::HTTP_CREATED,
        );
    }
}

