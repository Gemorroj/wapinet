<?php

namespace App\Controller\Api\Auth;

use App\Dto\Api\Auth\ApiTokenListResponse;
use App\Dto\Api\Auth\ApiTokenResponse;
use App\Dto\Api\Auth\LoginRequest;
use App\Dto\Api\Auth\TokenResponse;
use App\Dto\Api\User\RegisterRequest;
use App\Dto\Api\User\RegisterResponse;
use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Service\Api\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_')]
#[OA\Tag(name: 'Auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokens,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Issues an API token for valid credentials.
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(ref: new Model(type: LoginRequest::class)),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Issued API token',
        content: new Model(type: TokenResponse::class),
    )]
    #[OA\Response(response: 401, description: 'Invalid credentials')]
    #[OA\Response(response: 429, description: 'Too many login attempts')]
    public function login(): never
    {
        // JSON requests are intercepted by json_login; anything else reaches this fallback.
        throw new UnsupportedMediaTypeHttpException('Login expects an application/json request.');
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Response(response: 204, description: 'Current token revoked')]
    public function logout(Request $request): Response
    {
        $token = $this->currentToken($request);

        if (null !== $token) {
            $this->entityManager->remove($token);
            $this->entityManager->flush();
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/tokens', name: 'tokens', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Active API tokens of the current user',
        content: new Model(type: ApiTokenListResponse::class),
    )]
    public function tokens(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $current = $this->currentToken($request);
        $items = \array_map(
            static fn (ApiToken $token): ApiTokenResponse => ApiTokenResponse::fromToken(
                $token,
                $token->getId() === $current?->getId(),
            ),
            $this->apiTokens->findActiveByUser($user),
        );

        return $this->json(new ApiTokenListResponse($items));
    }

    #[Route('/tokens/{id}', name: 'token_revoke', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Token revoked')]
    #[OA\Response(response: 403, description: 'Access denied')]
    #[OA\Response(response: 404, description: 'Token not found')]
    public function revoke(int $id, #[CurrentUser] User $user): Response
    {
        $token = $this->apiTokens->find($id);

        if (null === $token) {
            throw new NotFoundHttpException('Token not found.');
        }

        if (!$token->getUser()->isEqualTo($user) && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $this->entityManager->remove($token);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: RegisterRequest::class)),
    )]
    #[OA\Response(
        response: 201,
        description: 'Registered user',
        content: new Model(type: RegisterResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Captcha verification failed')]
    #[OA\Response(response: 422, description: 'Validation error, username or email is already taken')]
    #[OA\Response(response: 429, description: 'Too many registration attempts')]
    public function register(
        #[MapRequestPayload]
        RegisterRequest $payload,
        Request $request,
        UserService $userService,
    ): JsonResponse {
        $user = $userService->register($payload, $request);

        return $this->json(new RegisterResponse($user->getUsername()), Response::HTTP_CREATED);
    }

    private function currentToken(Request $request): ?ApiToken
    {
        $plainToken = $this->bearerToken($request);

        if (null === $plainToken) {
            return null;
        }

        return $this->apiTokens->findOneBy(['tokenHash' => ApiToken::hash($plainToken)]);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->headers->get('Authorization', '');

        if (1 !== \preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
