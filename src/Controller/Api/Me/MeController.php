<?php

namespace App\Controller\Api\Me;

use App\Dto\Api\User\UserResponse;
use App\Entity\User;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/me', name: 'api_me', methods: ['GET'])]
#[OA\Tag(name: 'Auth')]
final class MeController extends AbstractController
{
    #[OA\Response(
        response: 200,
        description: 'Current user',
        content: new Model(type: UserResponse::class),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(UserResponse::fromUser($user));
    }
}
